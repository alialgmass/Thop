<?php

namespace Modules\Notifications\Providers;

use Illuminate\Support\Facades\Event;
use Modules\Notifications\Contracts\PushSender;
use Modules\Notifications\Contracts\SmsSender;
use Modules\Notifications\Listeners\NotificationEventSubscriber;
use Nwidart\Modules\Support\ModuleServiceProvider;

class NotificationsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Notifications';

    protected string $nameLower = 'notifications';

    /**
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->bind(PushSender::class, function (): PushSender {
            $driver = config('notifications.push.driver', 'log');

            return $this->app->make(config("notifications.push.drivers.{$driver}"));
        });

        $this->app->bind(SmsSender::class, function (): SmsSender {
            $driver = config('notifications.sms.driver', 'log');

            return $this->app->make(config("notifications.sms.drivers.{$driver}"));
        });
    }

    public function boot(): void
    {
        parent::boot();

        $this->loadTranslationsFrom(module_path($this->name, 'lang'), 'notifications');

        // The single event → notification wiring point (US-NOT-19).
        Event::subscribe(NotificationEventSubscriber::class);
    }
}
