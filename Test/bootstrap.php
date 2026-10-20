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

namespace Magento\Framework\Event {
    interface ObserverInterface
    {
        public function execute(Observer $observer);
    }

    class Observer
    {
        private $event;

        public function __construct($event = null)
        {
            $this->event = $event;
        }

        public function getEvent()
        {
            return $this->event;
        }
    }
}

namespace Magento\Customer\Model {
    class Session
    {
    }
}

namespace Magento\Customer\Api\Data {
    interface CustomerInterface
    {
    }

    interface GroupInterface
    {
        const NOT_LOGGED_IN_ID = 0;
    }

    class CustomerInterfaceFactory
    {
    }
}

namespace Magento\Catalog\Model\Product\Attribute\Source {
    class Status
    {
        const STATUS_ENABLED = 1;
        const STATUS_DISABLED = 2;
    }
}

namespace Magento\Store\Model {
    interface ScopeInterface
    {
        const SCOPE_STORE = 'store';
    }
}

namespace Psr\Log {
    interface LoggerInterface
    {
        public function error($message, array $context = []);
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
