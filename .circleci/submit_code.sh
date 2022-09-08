#set -x

VERSION=$(grep -o '^ *"version": *"[0-9\.]*"' ../composer.json | awk '{print $2}' | sed -e 's/"\(.*\)"/\1/g')
WD=$(pwd)

# Create shared package update payload
SHARED_ARCH_NAME=code_shared.zip
cd ..
zip -r $SHARED_ARCH_NAME ./*

# Upload shared package update to Marketplace
cd "${WD}"
git clone git@github.com:PowerSync/TNW_EQP.git eqp --branch main
[ -f .env ] && cp .env eqp
mv ../$SHARED_ARCH_NAME eqp
cp -r sharedpackage/data eqp
cd eqp
bin/main $SHARED_ARCH_NAME $VERSION 0
RESULT=$?
rm data
rm $SHARED_ARCH_NAME
if [ $RESULT -ne 0 ]; then
    exit $RESULT
fi

# Create basic meta package update payload
cd "${WD}"
META_ARCH_NAME=code_meta.zip
SAMPLE=metapackage/composer.json.sample
TARGET=metapackage/composer.json
cp $SAMPLE $TARGET
sed -i "s/\$VERSION/$VERSION/" $TARGET
zip -j $META_ARCH_NAME $TARGET

# Upload basic meta package to Marketplace
mv $META_ARCH_NAME eqp
cp -r metapackage/data eqp
cd eqp
TMP=$EQP_SHARED_MODULES
EQP_SHARED_MODULES=$EQP_SHARED_SKU # Rewrite EQP_SHARED_MODULES variable as original contains private modules
bin/main $META_ARCH_NAME $VERSION 1
EQP_SHARED_MODULES=$TMP # Restore EQP_SHARED_MODULES variable
RESULT=$?
rm $META_ARCH_NAME
exit $RESULT

# Create advanced meta package update payload
if [[ -z "${EQP_ADVANCED_META_SKU}" ]]; then
  echo 'Environment variable EQP_ADVANCED_META_SKU not defined.'
  exit 1
fi
if [[ -z "${EQP_SHARED_MODULES}" ]]; then
  echo 'Environment variable EQP_SHARED_MODULES not defined.'
  exit 2
fi
if [[ -z "${EQP_BASIC_SKU}" ]]; then
  echo 'Environment variable EQP_BASIC_SKU not defined.'
  exit 3
fi
if [[ -z "${EQP_ENTERPRISE_SKU}" ]]; then
  echo 'Environment variable EQP_ENTERPRISE_SKU not defined.'
  exit 4
fi
if [[ -z "${EQP_BUSINESS_SKU}" ]]; then
  echo 'Environment variable EQP_BUSINESS_SKU not defined.'
  exit 5
fi
cd "${WD}"
ADVANCED_META_ARCH_NAME=code_meta_advanced.zip
SAMPLE=advancedmetapackage/composer.json.sample
TARGET=advancedmetapackage/composer.json
cp $SAMPLE $TARGET
cd eqp
bin/get_next_version "${EQP_ADVANCED_META_SKU}"
bin/get_package_versions "${EQP_SHARED_MODULES}"
ENCODED_SKU=$(php -r "echo urlencode('"EQP_ADVANCED_META_SKU"');")
NEXT_VERSION=$(cat "tmp/next/${ENCODED_SKU}")
BASIC_ENCODED_SKU=$(php -r "echo urlencode('"EQP_BASIC_SKU"');")
BASIC_VERSION=$(cat "tmp/shared/${BASIC_ENCODED_SKU}")
ENTERPRISE_ENCODED_SKU=$(php -r "echo urlencode('"EQP_ENTERPRISE_SKU"');")
ENTERPRISE_VERSION=$(cat "tmp/shared/${ENTERPRISE_ENCODED_SKU}"))
BUSINESS_ENCODED_SKU=$(php -r "echo urlencode('"EQP_BUSINESS_SKU"');")
BUSINESS_VERSION=$(cat "tmp/shared/${BUSINESS_ENCODED_SKU}")
cd "${WD}"
sed -i "s/\$EQP_ADVANCED_META_SKU/$EQP_ADVANCED_META_SKU/" "$TARGET"
sed -i "s/\$VERSION/$NEXT_VERSION/" "$TARGET"
sed -i "s/\$EQP_BASIC_SKU/$EQP_BASIC_SKU/" "$TARGET"
sed -i "s/\$BASIC_VERSION/$BASIC_VERSION/" "$TARGET"
sed -i "s/\$EQP_ENTERPRISE_SKU/$EQP_ENTERPRISE_SKU/" "$TARGET"
sed -i "s/\$ENTERPRISE_VERSION/$ENTERPRISE_VERSION/" "$TARGET"
sed -i "s/\$EQP_BUSINESS_SKU/$EQP_BUSINESS_SKU/" "$TARGET"
sed -i "s/\$BUSINESS_VERSION/$BUSINESS_VERSION/" "$TARGET"
zip -j "$ADVANCED_META_ARCH_NAME" "$TARGET"

# Upload advanced meta package to Marketplace
mv $ADVANCED_META_ARCH_NAME eqp
cp -r advancedmetapackage/data eqp
cd eqp
bin/main $ADVANCED_META_ARCH_NAME "$VERSION" 1 "$EQP_ADVANCED_META_SKU"
RESULT=$?
rm $ADVANCED_META_ARCH_NAME
exit $RESULT
