<?php

namespace Modules\Chat\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Policies\ConversationPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ChatServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Chat';

    protected string $nameLower = 'chat';

    /**
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $this->loadTranslationsFrom(module_path($this->name, 'lang'), 'chat');

        // One authorization source (US-CHT-02): the same policy gates the REST
        // endpoints and the Pusher private-channel auth callback in routes/channels.php.
        Gate::policy(Conversation::class, ConversationPolicy::class);
    }
}
