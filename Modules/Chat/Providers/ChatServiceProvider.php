<?php

namespace Modules\Chat\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\Message;
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

        // Report a chat message through the Inquiries `reports` table — the
        // `message` alias mirrors Inquiries' own `ReportableType` morph map
        // (non-enforcing, additive).
        Relation::morphMap(['message' => Message::class]);

        // One authorization source (US-CHT-02): the same policy gates the REST
        // endpoints and the Pusher private-channel auth callback in routes/channels.php.
        Gate::policy(Conversation::class, ConversationPolicy::class);
    }
}
