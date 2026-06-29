<?php

namespace App\Filament\Pages;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Services\CacheService;
use App\Support\NavIcons;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page as FilamentPage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageMenus extends FilamentPage
{
    protected static string|\UnitEnum|null $navigationGroup = 'Tasarım & Yapılandırma';

    protected static ?string $navigationLabel = 'Menü Yönetimi';

    protected static ?string $title = 'Üst & Alt Menü';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static ?string $slug = 'menu-yonetimi';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'header_items' => $this->loadMenuItems('header'),
            'footer_items' => $this->loadMenuItems('footer'),
            'footer_bottom_items' => $this->loadMenuItems('footer_bottom'),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('menuTabs')
                ->tabs([
                    Tab::make('header')
                        ->label('Üst Menü (Header)')
                        ->icon(Heroicon::OutlinedArrowUp)
                        ->schema([
                            Section::make('Üst menü öğeleri')
                                ->description('Üst menüde görünen tüm bağlantılar. Tek bağlantı, klasik alt menü, mega menü veya geniş mega menü ekleyebilirsiniz. Sürükleyerek sıralayın.')
                                ->schema([
                                    $this->menuRepeater('header_items'),
                                ]),
                        ]),
                    Tab::make('footer')
                        ->label('Alt Menü (Footer)')
                        ->icon(Heroicon::OutlinedArrowDown)
                        ->schema([
                            Section::make('Footer sütunları')
                                ->description('Her sütun bir başlık ve altındaki bağlantılardan oluşur (ör. Hizmetler, Kurumsal, Yasal). Marka/iletişim sütunu ayarlardan gelir. Sürükleyerek sıralayın.')
                                ->schema([
                                    $this->footerColumnRepeater('footer_items'),
                                ]),
                        ]),
                    Tab::make('footer_bottom')
                        ->label('Alt Bilgi Şeridi')
                        ->icon(Heroicon::OutlinedMinus)
                        ->schema([
                            Section::make('Footer alt şerit bağlantıları')
                                ->description('Footer en altında, telif hakkı satırının yanında görünen kısa bağlantılar (ör. Gizlilik, KVKK, Çerez).')
                                ->schema([
                                    $this->flatLinkRepeater('footer_bottom_items'),
                                ]),
                        ]),
                ])
                ->persistTabInQueryString('menu'),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('menu-form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Menüleri Kaydet')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->syncMenu('header', 'Üst Menü', $data['header_items'] ?? []);
        $this->syncMenu('footer', 'Alt Menü', $data['footer_items'] ?? []);
        $this->syncMenu('footer_bottom', 'Alt Bilgi Şeridi', $data['footer_bottom_items'] ?? []);

        $cache = app(\App\Services\CacheInvalidator::class);
        $cache->forMenusSaved();

        Notification::make()
            ->title('Menüler kaydedildi')
            ->success()
            ->send();
    }

    protected function menuRepeater(string $name): Repeater
    {
        return Repeater::make($name)
            ->label('Öğeler')
            ->schema($this->menuItemFields())
            ->reorderableWithDragAndDrop()
            ->collapsible()
            ->cloneable()
            ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Yeni menü öğesi')
            ->addActionLabel('Menü öğesi ekle')
            ->defaultItems(0)
            ->columnSpanFull();
    }

    protected function footerColumnRepeater(string $name): Repeater
    {
        return Repeater::make($name)
            ->label('Sütunlar')
            ->schema([
                Hidden::make('id'),
                TextInput::make('label')
                    ->label('Sütun başlığı')
                    ->required()
                    ->maxLength(120)
                    ->columnSpan(3),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->columnSpan(1),
                Repeater::make('children')
                    ->label('Bağlantılar')
                    ->schema($this->menuItemFields(false))
                    ->reorderableWithDragAndDrop()
                    ->collapsible()
                    ->cloneable()
                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Bağlantı')
                    ->addActionLabel('Bağlantı ekle')
                    ->defaultItems(0)
                    ->columnSpanFull(),
            ])
            ->columns(4)
            ->reorderableWithDragAndDrop()
            ->collapsible()
            ->cloneable()
            ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Yeni sütun')
            ->addActionLabel('Sütun ekle')
            ->defaultItems(0)
            ->columnSpanFull();
    }

    protected function flatLinkRepeater(string $name): Repeater
    {
        return Repeater::make($name)
            ->label('Bağlantılar')
            ->schema($this->menuItemFields(false))
            ->reorderableWithDragAndDrop()
            ->collapsible()
            ->cloneable()
            ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Bağlantı')
            ->addActionLabel('Bağlantı ekle')
            ->defaultItems(0)
            ->columnSpanFull();
    }

    /** @return list<\Filament\Forms\Components\Component> */
    protected function menuItemFields(bool $withDropdown = true): array
    {
        $fields = [
            Hidden::make('id'),
            TextInput::make('label')
                ->label('Görünen ad')
                ->required()
                ->maxLength(120)
                ->columnSpan(2),
            Select::make('link_type')
                ->label('Bağlantı türü')
                ->options([
                    'page' => 'Site sayfası',
                    'url' => 'Özel URL',
                    'home' => 'Ana Sayfa',
                    'products' => 'Ürünler',
                    'blog' => 'Blog',
                    'contact' => 'İletişim',
                ])
                ->default('url')
                ->live()
                ->required()
                ->columnSpan(2),
            Select::make('page_id')
                ->label('Sayfa')
                ->options(fn () => Page::query()->where('is_published', true)->orderBy('title')->pluck('title', 'id'))
                ->searchable()
                ->visible(fn (Get $get): bool => $get('link_type') === 'page')
                ->required(fn (Get $get): bool => $get('link_type') === 'page')
                ->columnSpan(2),
            TextInput::make('url')
                ->label('URL')
                ->placeholder('/sayfa/hakkimizda veya https://...')
                ->visible(fn (Get $get): bool => $get('link_type') === 'url')
                ->required(fn (Get $get): bool => $get('link_type') === 'url')
                ->columnSpan(2),
            Select::make('target')
                ->label('Açılış')
                ->options([
                    '_self' => 'Aynı sekme',
                    '_blank' => 'Yeni sekme',
                ])
                ->default('_self')
                ->columnSpan(1),
            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true)
                ->columnSpan(1),
        ];

        if ($withDropdown) {
            $fields = array_merge($fields, [
                Select::make('dropdown_style')
                    ->label('Alt menü tipi')
                    ->options([
                        '' => 'Yok (tek bağlantı)',
                        'dropdown' => 'Klasik alt menü',
                        'mega' => 'Mega menü',
                        'mega_wide' => 'Geniş mega menü',
                    ])
                    ->live()
                    ->columnSpan(2),
                Select::make('icon')
                    ->label('Simge')
                    ->options(NavIcons::options())
                    ->columnSpan(2),
                TextInput::make('badge')
                    ->label('Rozet (opsiyonel)')
                    ->placeholder('Yeni')
                    ->maxLength(40)
                    ->columnSpan(2),
                TextInput::make('description')
                    ->label('Kısa açıklama')
                    ->maxLength(500)
                    ->visible(fn (Get $get): bool => in_array($get('dropdown_style'), ['mega', 'mega_wide'], true))
                    ->columnSpanFull(),
                Repeater::make('children')
                    ->label('Alt menü öğeleri')
                    ->schema($this->menuItemFields(false))
                    ->visible(fn (Get $get): bool => in_array($get('dropdown_style'), ['dropdown', 'mega', 'mega_wide'], true))
                    ->reorderableWithDragAndDrop()
                    ->collapsible()
                    ->cloneable()
                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Alt öğe')
                    ->addActionLabel('Alt öğe ekle')
                    ->columnSpanFull(),
                Section::make('Mega menü bilgi paneli (sağ)')
                    ->description('İsteğe bağlı — mega menüde sağ tarafta gösterilir.')
                    ->schema([
                        TextInput::make('panel_title')->label('Panel başlık')->maxLength(120),
                        Textarea::make('panel_text')->label('Panel metin')->rows(3),
                        TextInput::make('panel_cta_label')->label('CTA metni')->maxLength(80),
                        TextInput::make('panel_cta_url')->label('CTA URL')->maxLength(500),
                    ])
                    ->visible(fn (Get $get): bool => in_array($get('dropdown_style'), ['mega', 'mega_wide'], true))
                    ->collapsed()
                    ->columnSpanFull(),
            ]);
        }

        return $fields;
    }

    /** @return list<array<string, mixed>> */
    protected function loadMenuItems(string $location): array
    {
        $menu = Menu::where('location', $location)->first();

        if (! $menu) {
            return [];
        }

        return $menu->items()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->with('children')
            ->get()
            ->map(fn (MenuItem $item) => $this->mapMenuItem($item))
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    protected function mapMenuItem(MenuItem $item): array
    {
        return [
            'id' => $item->id,
            'label' => $item->label,
            'link_type' => $this->resolveLinkType($item),
            'page_id' => $item->page_id,
            'url' => $item->url,
            'target' => $item->safe_target,
            'is_active' => $item->is_active,
            'dropdown_style' => $item->dropdown_style ?? '',
            'icon' => $item->icon ?? '',
            'description' => $item->description,
            'badge' => $item->badge,
            'panel_title' => $item->panel_title,
            'panel_text' => $item->panel_text,
            'panel_cta_label' => $item->panel_cta_label,
            'panel_cta_url' => $item->panel_cta_url,
            'children' => $item->children->sortBy('sort_order')->map(fn (MenuItem $child) => [
                'id' => $child->id,
                'label' => $child->label,
                'link_type' => $this->resolveLinkType($child),
                'page_id' => $child->page_id,
                'url' => $child->url,
                'target' => $child->safe_target,
                'is_active' => $child->is_active,
                'icon' => $child->icon ?? '',
                'description' => $child->description,
                'badge' => $child->badge,
            ])->values()->all(),
        ];
    }

    protected function resolveLinkType(MenuItem $item): string
    {
        if ($item->page_id) {
            return 'page';
        }

        $url = trim($item->url ?? '');

        return match ($url) {
            '/', '/anasayfa' => 'home',
            '/urunler' => 'products',
            '/blog' => 'blog',
            '/iletisim' => 'contact',
            default => 'url',
        };
    }

    /** @param list<array<string, mixed>> $items */
    protected function syncMenu(string $location, string $name, array $items): void
    {
        $menu = Menu::firstOrCreate(
            ['location' => $location],
            ['name' => $name],
        );

        $keptIds = [];

        foreach (array_values($items) as $order => $item) {
            $created = $this->upsertMenuItem($menu, $item, null, $order, $keptIds);

            foreach (array_values($item['children'] ?? []) as $childOrder => $child) {
                $this->upsertMenuItem($menu, $child, $created->id, $childOrder, $keptIds);
            }
        }

        $menu->items()->whereNotIn('id', $keptIds)->delete();
    }

    /** @param array<string, mixed> $item @param list<int> $keptIds */
    protected function upsertMenuItem(Menu $menu, array $item, ?int $parentId, int $order, array &$keptIds): MenuItem
    {
        [$pageId, $url] = $this->resolveLinkTarget($item);

        $payload = [
            'parent_id' => $parentId,
            'label' => $item['label'],
            'page_id' => $pageId,
            'url' => $url,
            'target' => in_array($item['target'] ?? '_self', ['_self', '_blank'], true) ? $item['target'] : '_self',
            'is_active' => (bool) ($item['is_active'] ?? true),
            'sort_order' => $order,
            'dropdown_style' => ($item['dropdown_style'] ?? '') !== '' ? $item['dropdown_style'] : null,
            'icon' => ($item['icon'] ?? '') !== '' ? $item['icon'] : null,
            'description' => $item['description'] ?? null,
            'badge' => $item['badge'] ?? null,
            'panel_title' => $item['panel_title'] ?? null,
            'panel_text' => $item['panel_text'] ?? null,
            'panel_cta_label' => $item['panel_cta_label'] ?? null,
            'panel_cta_url' => $item['panel_cta_url'] ?? null,
        ];

        if ($parentId !== null) {
            unset($payload['dropdown_style'], $payload['panel_title'], $payload['panel_text'], $payload['panel_cta_label'], $payload['panel_cta_url']);
        }

        if (! empty($item['id'])) {
            $existing = MenuItem::where('menu_id', $menu->id)->where('id', $item['id'])->first();
            if ($existing) {
                $existing->update($payload);
                $keptIds[] = $existing->id;

                return $existing;
            }
        }

        $created = $menu->items()->create($payload);
        $keptIds[] = $created->id;

        return $created;
    }

    /** @param array<string, mixed> $item @return array{0: ?int, 1: ?string} */
    protected function resolveLinkTarget(array $item): array
    {
        $linkType = $item['link_type'] ?? 'url';

        return match ($linkType) {
            'page' => [(int) ($item['page_id'] ?? 0) ?: null, null],
            'home' => [null, '/'],
            'products' => [null, '/urunler'],
            'blog' => [null, '/blog'],
            'contact' => [null, '/iletisim'],
            default => [null, trim($item['url'] ?? '') ?: null],
        };
    }
}
