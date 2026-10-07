<?php

namespace Magento\Framework\App\Action {
    class Action
    {
    }
}

namespace Magento\Framework\View\Element {
    class Template
    {
    }
}

namespace Magento\Framework\Data {
    class Collection
    {
        public const SORT_ORDER_ASC = 'ASC';
        public const SORT_ORDER_DESC = 'DESC';
    }
}

namespace {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'Clerk\\Clerk\\';
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $file = dirname(__DIR__) . '/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($file)) {
            require $file;
        }
    });
}
