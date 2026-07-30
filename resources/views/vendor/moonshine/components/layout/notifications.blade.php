@php
    use Illuminate\Notifications\DatabaseNotification;

    $user = auth(config('moonshine.auth.guard'))?->user();
    $initialCount = $user ? $user->unreadNotifications()
        ->where('type', DatabaseNotification::class)
        ->where(function ($q) { $q->whereNull('expires_at')->orWhere('expires_at', '>', now()); })
        ->count() : 0;
@endphp

<div
        x-data="notificationCenter()"
        x-init="init()"
        class="notifications"
>
    <x-moonshine::dropdown
            placement="bottom-end"
            title="Уведомления"
            class="w-[264px] xs:w-80"
    >
        <x-slot:toggler class="notifications-trigger" @click="trackOpened()">
            <span
                    x-show="count > 0"
                    x-cloak
                    class="notifications-trigger-dot"
            ></span>
            <x-moonshine::icon icon="bell" class="size-6!" />
        </x-slot:toggler>

        <template x-if="items.length === 0">
            <div class="px-4 py-8 text-center text-sm text-slate-400 dark:text-slate-500">
                <x-moonshine::icon icon="inbox" size="8" class="mx-auto mb-2 opacity-50" />
                Нет новых уведомлений
            </div>
        </template>

        <template x-for="item in items" :key="item.id">
            <div class="notifications-item">
                <form @submit.prevent="markAsRead(item.id)" class="notifications-remove">
                    @csrf
                    <button type="submit" title="Отметить как прочитанное">
                        <x-moonshine::icon icon="x-mark" />
                    </button>
                </form>

                <div class="notifications-category" :class="`text-${item.color}`">
                    <x-moonshine::icon icon="information-circle" ::icon="item.icon" />
                </div>

                <div class="notifications-content">
                    <p class="notifications-text" x-text="item.message"></p>

                    <template x-if="item.button">
                        <div class="notifications-more">
                            <a
                                    :href="item.button.link"
                                    :target="item.button.attributes?.target || '_self'"
                                    x-text="item.button.label"
                            ></a>
                        </div>
                    </template>

                    <span class="notifications-time" x-text="formatDate(item.created_at)"></span>
                </div>
            </div>
        </template>

        <x-slot:footer>
            <form @submit.prevent="markAllAsRead()">
                @csrf
                <button type="submit" class="notifications-read">
                    Прочитать все
                </button>
            </form>
        </x-slot:footer>
    </x-moonshine::dropdown>

    <script>
        function notificationCenter() {
            return {
                count: {{ $initialCount }},
                items: [],
                loading: false,
                lastFetch: null,
                pollingInterval: null,
                openedTracked: new Set(),
                initialized: false,

                init() {
                    if (this.initialized) return;
                    this.initialized = true;

                    setTimeout(() => this.fetchUnread(true), 1000);

                    this.pollingInterval = setInterval(() => {
                        this.fetchUnread(false);
                    }, 30000);

                    document.addEventListener('visibilitychange', () => {
                        if (document.hidden) return;
                        const sinceLast = this.lastFetch ? Date.now() - this.lastFetch : Infinity;
                        if (sinceLast > 25000) {
                            this.fetchUnread(false);
                        }
                    });
                },

                async fetchUnread(force = false) {
                    if (this.loading) return;
                    if (!force && this.lastFetch && (Date.now() - this.lastFetch) < 5000) return;

                    this.loading = true;

                    try {
                        const response = await fetch(
                            '{{ route("notifications.unread") }}',
                            {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            }
                        );

                        if (!response.ok) throw new Error('Network error');

                        const data = await response.json();
                        this.count = data.count;
                        this.items = data.items;
                        this.lastFetch = Date.now();
                    } catch (e) {
                        console.warn('Notification polling failed:', e);
                    } finally {
                        this.loading = false;
                    }
                },

                trackOpened() {
                    const ids = this.items
                        .filter(item => !this.openedTracked.has(item.id))
                        .map(item => item.id);

                    if (ids.length === 0) return;

                    ids.forEach(id => this.openedTracked.add(id));

                    fetch('{{ route("notifications.opened") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ ids }),
                    }).catch(() => {});
                },

                async markAsRead(id) {
                    try {
                        const response = await fetch(`/api/notifications/${id}/read`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            credentials: 'same-origin',
                        });

                        if (response.ok) {
                            this.items = this.items.filter(i => i.id !== id);
                            this.count = Math.max(0, this.count - 1);
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                async markAllAsRead() {
                    try {
                        const response = await fetch('{{ route("notifications.read-all") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            credentials: 'same-origin',
                        });

                        if (response.ok) {
                            this.items = [];
                            this.count = 0;
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                formatDate(isoString) {
                    if (!isoString) return '';
                    const date = new Date(isoString);
                    return date.toLocaleDateString('ru-RU', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                    });
                },
            }
        }
    </script>
</div>
