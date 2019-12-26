<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Model\Config\Backend;

use \Magento\Config\Model\Config\CommentInterface;

/**
 * Class CallBackComment
 * @package TNW\QuickbooksBasic\Model\Config\Backend
 */
class CallBackComment implements CommentInterface
{
    /**
     * @var \Magento\Framework\Url
     */
    protected $urlBuilder;

    /**
     * CallBackComment constructor.
     * @param \Magento\Framework\Url $urlBuilder
     */
    public function __construct(
        \Magento\Framework\Url $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param string $elementValue
     * @return \Magento\Framework\Phrase|string
     */
    public function getCommentText($elementValue)
    {
        $comment = __(
            'Type of the QuickBooks account you are connecting to. <br> CallBack Url For QB app configuration: %1',
            $this->urlBuilder->getUrl(\TNW\QuickbooksBasic\Model\Config::CALLBACK_ROUTE_PATH)
        );
        return $comment;
    }
}
