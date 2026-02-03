<?php

namespace Modular\Fields;

/**
 * Central field registry. Single source of truth for field types and behavior.
 * Admin and core both use this.
 */
class FieldRegistry
{
    public const CATEGORY_BASIC = 'basic';
    public const CATEGORY_CONTENT = 'content';
    public const CATEGORY_CHOICE = 'choice';
    public const CATEGORY_RELATIONAL = 'relational';
    public const CATEGORY_MEDIA = 'media';
    public const CATEGORY_DATE_TIME = 'date_time';
    public const CATEGORY_LAYOUT = 'layout';
    public const CATEGORY_UTILITY = 'utility';

    /** @var array<string, FieldInterface> */
    private static $fields = [];

    /** Types that are aliases; shown only once in admin (canonical type is the main one). */
    private static $adminHiddenAliases = ['relation'];

    public static function register(string $type, FieldInterface $field): void
    {
        self::$fields[$type] = $field;
    }

    public static function get(string $type): ?FieldInterface
    {
        return self::$fields[$type] ?? null;
    }

    /**
     * List all registered types for admin Field Library.
     * Returns flat list (each item: type, label, icon, category, config_schema) for backward compat.
     */
    public static function listForAdmin(): array
    {
        $flat = [];
        $order = [
            self::CATEGORY_BASIC,
            self::CATEGORY_CONTENT,
            self::CATEGORY_CHOICE,
            self::CATEGORY_RELATIONAL,
            self::CATEGORY_MEDIA,
            self::CATEGORY_DATE_TIME,
            self::CATEGORY_LAYOUT,
            self::CATEGORY_UTILITY,
        ];
        $grouped = [];
        foreach (self::$fields as $type => $field) {
            if (in_array($type, self::$adminHiddenAliases, true)) {
                continue;
            }
            $config = $field->adminConfig();
            $input = $field->adminInput([]);
            $category = $config['category'] ?? self::CATEGORY_BASIC;
            $item = [
                'type' => $type,
                'label' => $config['label'] ?? $input['label'] ?? ucfirst($type),
                'icon' => $config['icon'] ?? 'fa-circle',
                'category' => $category,
                'config_schema' => $input['config_schema'] ?? [],
            ];
            $flat[] = $item;
            $grouped[$category] = $grouped[$category] ?? [];
            $grouped[$category][] = $item;
        }
        // Sort flat by category order
        usort($flat, static function ($a, $b) use ($order) {
            $pa = array_search($a['category'], $order, true);
            $pb = array_search($b['category'], $order, true);
            if ($pa === false) {
                $pa = 999;
            }
            if ($pb === false) {
                $pb = 999;
            }
            return $pa <=> $pb;
        });
        return $flat;
    }

    /**
     * Grouped by category for Field Library UI.
     */
    public static function listForAdminGrouped(): array
    {
        $grouped = [];
        foreach (self::$fields as $type => $field) {
            if (in_array($type, self::$adminHiddenAliases, true)) {
                continue;
            }
            $config = $field->adminConfig();
            $input = $field->adminInput([]);
            $category = $config['category'] ?? self::CATEGORY_BASIC;
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = [
                'type' => $type,
                'label' => $config['label'] ?? $input['label'] ?? ucfirst($type),
                'icon' => $config['icon'] ?? 'fa-circle',
                'category' => $category,
                'config_schema' => $input['config_schema'] ?? [],
            ];
        }
        return $grouped;
    }

    /** Utility field types: never stored in post data. */
    public static function getUtilityTypes(): array
    {
        return ['message', 'accordion', 'tab', 'divider'];
    }

    public static function isUtilityType(string $type): bool
    {
        return in_array($type, self::getUtilityTypes(), true);
    }

    public static function isLayoutType(string $type): bool
    {
        $field = self::get($type);
        return $field instanceof LayoutFieldInterface;
    }

    /**
     * Register all built-in field types. Call once during bootstrap.
     */
    public static function bootDefaultFields(): void
    {
        if (!empty(self::$fields)) {
            return;
        }

        // Basic
        self::register('text', new TextField());
        self::register('textarea', new TextareaField());
        self::register('number', new NumberField());
        self::register('email', new EmailField());
        self::register('url', new URLField());
        self::register('password', new PasswordField());

        // Content
        self::register('wysiwyg', new WysiwygField());
        self::register('code', new CodeField());
        self::register('markdown', new MarkdownField());

        // Choice
        self::register('select', new SelectField());
        self::register('checkbox', new CheckboxField());
        self::register('radio', new RadioField());
        self::register('button_group', new ButtonGroupField());
        self::register('true_false', new TrueFalseField());

        // Relational
        self::register('post_object', new PostObjectField());
        self::register('relationship', new RelationField());
        self::register('relation', new RelationField()); // alias for backward compat
        self::register('taxonomy', new TaxonomyField());
        self::register('user', new UserField());

        // Media
        self::register('image', new ImageField());
        self::register('file', new FileField());
        self::register('gallery', new GalleryField());
        self::register('audio', new AudioField());
        self::register('video', new VideoField());
        self::register('embed', new EmbedField());

        // Date & Time
        self::register('date_picker', new DatePickerField());
        self::register('time_picker', new TimePickerField());
        self::register('date_time_picker', new DateTimePickerField());

        // Layout
        self::register('group', new GroupField());
        self::register('repeater', new RepeaterField());
        self::register('flexible_content', new FlexibleContentField());

        // Utility (admin-only, not stored)
        self::register('message', new MessageField());
        self::register('accordion', new AccordionField());
        self::register('tab', new TabField());
        self::register('divider', new DividerField());
    }
}
