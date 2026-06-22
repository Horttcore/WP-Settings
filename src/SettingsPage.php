<?php

declare(strict_types=1);

/**
 * Settings API admin pages.
 *
 * @license GPL-2.0+
 */

namespace RalfHortt\Settings;

/**
 * Build admin settings pages and subpages with a fluent API.
 */
class SettingsPage
{
    /**
     * Current panel id.
     *
     * @var string
     */
    protected string $currentPanelId = '';

    /**
     * Current section id.
     *
     * @var string
     */
    protected string $currentSectionId = '';

    /**
     * Page title.
     *
     * @var string
     */
    protected string $pageTitle = 'Settings';

    /**
     * Menu title.
     *
     * @var string
     */
    protected string $menuTitle = 'Settings';

    /**
     * Menu slug.
     *
     * @var string
     */
    protected string $menuSlug = 'settings';

    /**
     * Required capability.
     *
     * @var string
     */
    protected string $capability = 'manage_options';

    /**
     * Parent slug. Null means top-level page.
     *
     * @var string|null
     */
    protected ?string $parentSlug = 'options-general.php';

    /**
     * Top-level menu icon.
     *
     * @var string
     */
    protected string $icon = 'dashicons-admin-generic';

    /**
     * Top-level menu position.
     *
     * @var int|null
     */
    protected ?int $position = null;

    /**
     * Panels.
     *
     * @var array<string, array{title: string, description: string, sections: array<string, bool>}>
     */
    protected array $panels = [];

    /**
     * Sections.
     *
     * @var array<string, array{title: string, description: string, panel: string}>
     */
    protected array $sections = [];

    /**
     * Fields.
     *
     * @var array<string, array{
     *     identifier: string,
     *     label: string,
     *     type: string,
     *     default: mixed,
     *     capability: string,
     *     description: string,
     *     choices: array<string, string>,
     *     section: string,
     *     sanitize_callback: callable|string|null
     * }>
     */
    protected array $fields = [];

    /**
     * Construct.
     *
     * @param bool $autoInit Auto-register hooks.
     */
    public function __construct(bool $autoInit = true)
    {
        if ($autoInit) {
            $this->register();
        }
    }

    /**
     * Configure page metadata.
     *
     * @param string      $title      Page title.
     * @param string|null $menuTitle  Menu title.
     * @param string|null $slug       Page slug.
     * @param string      $capability Capability required.
     * @param string      $icon       Dashicon for top-level pages.
     * @param int|null    $position   Menu position for top-level pages.
     *
     * @return self
     */
    public function page(
        string $title,
        ?string $menuTitle = null,
        ?string $slug = null,
        string $capability = 'manage_options',
        string $icon = 'dashicons-admin-generic',
        ?int $position = null
    ): self {
        $this->pageTitle = $title;
        $this->menuTitle = $menuTitle ?: $title;
        $this->menuSlug = $slug ?: \sanitize_title($title);
        $this->capability = $capability;
        $this->icon = $icon;
        $this->position = $position;

        return $this;
    }

    /**
     * Register this page as a subpage under a parent menu.
     *
     * @param string $parentSlug Parent admin menu slug.
     *
     * @return self
     */
    public function subpageUnder(string $parentSlug = 'options-general.php'): self
    {
        $this->parentSlug = $parentSlug;

        return $this;
    }

    /**
     * Register this page as top-level menu page.
     *
     * @param string   $icon     Dashicon slug.
     * @param int|null $position Menu position.
     *
     * @return self
     */
    public function topLevel(string $icon = 'dashicons-admin-generic', ?int $position = null): self
    {
        $this->parentSlug = null;
        $this->icon = $icon;
        $this->position = $position;

        return $this;
    }

    /**
     * Register a panel.
     *
    * @param string                                                                 $label Panel title.
    * @param array{title?: string, description?: string, sections?: array<string, bool>} $args Optional panel args.
     *
     * @return self
     */
    public function panel(string $label, array $args = []): self
    {
        $identifier = \sanitize_title($label);
        $this->currentPanelId = $identifier;

        /** @var array{title: string, description: string, sections: array<string, bool>} $panel */
        $panel = \wp_parse_args($args, [
            'title' => $label,
            'description' => '',
            'sections' => [],
        ]);

        $this->panels[$identifier] = $panel;

        return $this;
    }

    /**
     * Register a section.
     *
    * @param string                                                       $label Section title.
    * @param array{title?: string, description?: string, panel?: string} $args Optional section args.
    * @param string                                                       $identifier Section identifier.
     *
     * @return self
     */
    public function section(string $label, array $args = [], string $identifier = ''): self
    {
        if (!$identifier) {
            $identifier = \sanitize_title($label);
        }

        /** @var array{title: string, description: string, panel: string} $section */
        $section = \wp_parse_args($args, [
            'title' => $label,
            'description' => '',
            'panel' => $this->currentPanelId,
        ]);

        $this->sections[$identifier] = $section;
        $this->currentSectionId = $identifier;

        if ($section['panel'] && isset($this->panels[$section['panel']])) {
            $this->panels[$section['panel']]['sections'][$identifier] = true;
        }

        return $this;
    }

    /**
     * Add a generic field.
     *
     * @param string               $identifier Identifier.
     * @param string               $label      Label.
     * @param string               $fieldType  Field type.
     * @param mixed                $default    Default value.
     * @param string               $capability Capability override.
     * @param string               $description Description.
     * @param array<string,string> $choices    Choices.
     * @param callable|string|null $sanitizeCallback Sanitize callback.
     *
     * @return self
     */
    public function add(
        string $identifier,
        string $label,
        string $fieldType = 'text',
        mixed $default = '',
        string $capability = '',
        string $description = '',
        array $choices = [],
        callable|string|null $sanitizeCallback = null
    ): self {
        /** @var array{identifier: string, label: string, type: string, default: mixed, capability: string, description: string, choices: array<string, string>, section: string, sanitize_callback: callable|string|null} $field */
        $field = [
            'identifier' => $identifier,
            'label' => $label,
            'type' => $fieldType,
            'default' => $default,
            'capability' => $capability,
            'description' => $description,
            'choices' => $choices,
            'section' => $this->currentSectionId,
            'sanitize_callback' => $sanitizeCallback,
        ];

        $this->fields[$identifier] = $field;

        return $this;
    }

    /**
     * Checkbox field.
     *
     * @return self
     */
    public function checkbox(
        string $identifier,
        string $label,
        mixed $default = 0,
        string $capability = '',
        string $description = ''
    ): self {
        return $this->addTypedField('checkbox', 'absint', $identifier, $label, $default, $capability, $description);
    }

    /**
     * Color field.
     *
     * @return self
     */
    public function color(
        string $identifier,
        string $label,
        mixed $default = '',
        string $capability = '',
        string $description = ''
    ): self {
        return $this->addTypedField('color', 'sanitize_hex_color', $identifier, $label, $default, $capability, $description);
    }

    /**
     * File field.
     *
     * @return self
     */
    public function file(
        string $identifier,
        string $label,
        mixed $default = '',
        string $capability = '',
        string $description = ''
    ): self {
        return $this->addTypedField('file', 'esc_url_raw', $identifier, $label, $default, $capability, $description);
    }

    /**
     * Image field.
     *
     * @return self
     */
    public function image(
        string $identifier,
        string $label,
        mixed $default = '',
        string $capability = '',
        string $description = ''
    ): self {
        return $this->addTypedField('image', 'esc_url_raw', $identifier, $label, $default, $capability, $description);
    }

    /**
     * Dropdown pages field.
     *
     * @return self
     */
    public function pageDropdown(
        string $identifier,
        string $label,
        mixed $default = 0,
        string $capability = '',
        string $description = ''
    ): self {
        return $this->addTypedField('page-dropdown', 'absint', $identifier, $label, $default, $capability, $description);
    }

    /**
     * Radio field.
     *
     * @param array<string,string> $choices Choices.
     *
     * @return self
     */
    public function radio(
        string $identifier,
        string $label,
        array $choices = [],
        mixed $default = '',
        string $capability = '',
        string $description = ''
    ): self {
        return $this->addTypedField('radio', 'sanitize_text_field', $identifier, $label, $default, $capability, $description, $choices);
    }

    /**
     * Select field.
     *
     * @param array<string,string> $choices Choices.
     *
     * @return self
     */
    public function select(
        string $identifier,
        string $label,
        array $choices = [],
        mixed $default = '',
        string $capability = '',
        string $description = ''
    ): self {
        return $this->addTypedField('select', 'sanitize_text_field', $identifier, $label, $default, $capability, $description, $choices);
    }

    /**
     * Text field.
     *
     * @return self
     */
    public function text(
        string $identifier,
        string $label,
        mixed $default = '',
        string $capability = '',
        string $description = ''
    ): self {
        return $this->addTypedField('text', 'sanitize_text_field', $identifier, $label, $default, $capability, $description);
    }

    /**
     * Textarea field.
     *
     * @return self
     */
    public function textarea(
        string $identifier,
        string $label,
        mixed $default = '',
        string $capability = '',
        string $description = ''
    ): self {
        return $this->addTypedField('textarea', 'wp_kses_post', $identifier, $label, $default, $capability, $description);
    }

    /**
     * URL field.
     *
     * @return self
     */
    public function url(
        string $identifier,
        string $label,
        mixed $default = '',
        string $capability = '',
        string $description = ''
    ): self {
        return $this->addTypedField('url', 'esc_url_raw', $identifier, $label, $default, $capability, $description);
    }

    /**
     * Register hooks.
     *
     * @return void
     */
    public function register(): void
    {
        \add_action('admin_menu', [$this, 'registerAdminPage']);
        \add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Register page in admin menu.
     *
     * @return void
     */
    public function registerAdminPage(): void
    {
        if ($this->parentSlug === null) {
            \add_menu_page(
                $this->pageTitle,
                $this->menuTitle,
                $this->capability,
                $this->menuSlug,
                [$this, 'renderPage'],
                $this->icon,
                $this->position
            );

            return;
        }

        \add_submenu_page(
            $this->parentSlug,
            $this->pageTitle,
            $this->menuTitle,
            $this->capability,
            $this->menuSlug,
            [$this, 'renderPage']
        );
    }

    /**
     * Register sections and fields with Settings API.
     *
     * @return void
     */
    public function registerSettings(): void
    {
        foreach ($this->sections as $sectionId => $section) {
            \add_settings_section(
                $sectionId,
                $section['title'],
                [$this, 'renderSection'],
                $this->menuSlug,
                ['id' => $sectionId, 'description' => $section['description'], 'panel' => $section['panel']]
            );
        }

        foreach ($this->fields as $fieldId => $field) {
            \register_setting(
                $this->menuSlug,
                $fieldId,
                ['sanitize_callback' => function (mixed $value) use ($fieldId): mixed {
                    return $this->sanitizeValue($fieldId, $value);
                }]
            );

            \add_settings_field(
                $fieldId,
                $field['label'],
                [$this, 'renderField'],
                $this->menuSlug,
                $field['section'],
                ['id' => $fieldId]
            );
        }
    }

    /**
     * Render page wrapper and settings form.
     *
     * @return void
     */
    public function renderPage(): void
    {
        $panelGroups = $this->getRenderablePanelGroups();

        echo '<div class="wrap">';
        echo '<h1>' . \esc_html($this->pageTitle) . '</h1>';
        echo '<form action="options.php" method="post">';
        \settings_fields($this->menuSlug);

        if (\count($panelGroups) > 1) {
            $this->renderTabbedPanelGroups($panelGroups);
            $this->renderTabbedPanelAssets();
        } else {
            $this->renderLinearPanelGroups($panelGroups);
        }

        \submit_button();
        echo '</form>';
        echo '</div>';
    }

    /**
     * Build panel groups for rendering.
     *
     * @return array<string, array{title: string, description: string, sections: array<int, string>}>
     */
    protected function getRenderablePanelGroups(): array
    {
        /** @var array<string, array{title: string, description: string, sections: array<int, string>}> $groups */
        $groups = [];

        foreach ($this->panels as $panelId => $panel) {
            $groups[$panelId] = [
                'title' => (string) $panel['title'],
                'description' => (string) $panel['description'],
                'sections' => [],
            ];
        }

        foreach ($this->sections as $sectionId => $section) {
            $panelId = (string) $section['panel'];

            if (!$panelId || !isset($groups[$panelId])) {
                $panelId = '__default';

                if (!isset($groups[$panelId])) {
                    $groups[$panelId] = [
                        'title' => 'General',
                        'description' => '',
                        'sections' => [],
                    ];
                }
            }

            $groups[$panelId]['sections'][] = $sectionId;
        }

        $groups = \array_filter($groups, static function (array $group): bool {
            return !empty($group['sections']);
        });

        if (!$groups) {
            return [];
        }

        return $groups;
    }

    /**
     * Render groups without tabs.
     *
     * @param array<string, array{title: string, description: string, sections: array<int, string>}> $panelGroups Groups.
     *
     * @return void
     */
    protected function renderLinearPanelGroups(array $panelGroups): void
    {
        if (!$panelGroups) {
            \do_settings_sections($this->menuSlug);

            return;
        }

        foreach ($panelGroups as $panelGroup) {
            $this->renderSinglePanelGroup($panelGroup);
        }
    }

    /**
     * Render groups as tabbed panels.
     *
     * @param array<string, array{title: string, description: string, sections: array<int, string>}> $panelGroups Groups.
     *
     * @return void
     */
    protected function renderTabbedPanelGroups(array $panelGroups): void
    {
        echo '<div class="wp-settings-tab-group" data-wp-settings-tab-group="1">';
        echo '<h2 class="nav-tab-wrapper">';

        $firstPanelId = '';
        foreach ($panelGroups as $panelId => $panelGroup) {
            if ($firstPanelId === '') {
                $firstPanelId = $panelId;
            }

            echo '<button type="button" class="nav-tab" data-wp-settings-tab="' . \esc_attr($panelId) . '">' . \esc_html($panelGroup['title']) . '</button>';
        }

        echo '</h2>';
        echo '<div class="wp-settings-tab-panels">';

        foreach ($panelGroups as $panelId => $panelGroup) {
            $isActive = $panelId === $firstPanelId;

            echo '<div class="wp-settings-tab-panel" data-wp-settings-panel="' . \esc_attr($panelId) . '"' . ($isActive ? '' : ' hidden') . '>';
            $this->renderSinglePanelGroup($panelGroup);
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * Render one panel group with all contained sections.
     *
     * @param array{title: string, description: string, sections: array<int, string>} $panelGroup Panel group.
     *
     * @return void
     */
    protected function renderSinglePanelGroup(array $panelGroup): void
    {
        if ($panelGroup['description'] !== '') {
            echo '<p>' . \esc_html($panelGroup['description']) . '</p>';
        }

        foreach ($panelGroup['sections'] as $sectionId) {
            if (!isset($this->sections[$sectionId])) {
                continue;
            }

            $section = $this->sections[$sectionId];

            echo '<section class="wp-settings-section" id="section-' . \esc_attr($sectionId) . '">';
            echo '<h2>' . \esc_html($section['title']) . '</h2>';

            if ($section['description'] !== '') {
                echo '<p>' . \esc_html($section['description']) . '</p>';
            }

            echo '<table class="form-table" role="presentation">';
            \do_settings_fields($this->menuSlug, $sectionId);
            echo '</table>';
            echo '</section>';
        }
    }

    /**
     * Render CSS and JS for panel tabs.
     *
     * @return void
     */
    protected function renderTabbedPanelAssets(): void
    {
        echo '<style>.wp-settings-tab-group .wp-settings-tab-panel{margin-top:16px;}.wp-settings-tab-group .wp-settings-section + .wp-settings-section{margin-top:24px;}</style>';
        echo '<script>(function(){var groups=document.querySelectorAll("[data-wp-settings-tab-group]");groups.forEach(function(group){if(group.dataset.wpSettingsTabsReady==="1"){return;}group.dataset.wpSettingsTabsReady="1";var tabs=group.querySelectorAll("[data-wp-settings-tab]");var panels=group.querySelectorAll("[data-wp-settings-panel]");if(!tabs.length||!panels.length){return;}var activate=function(panelId){tabs.forEach(function(tab){var active=tab.getAttribute("data-wp-settings-tab")===panelId;tab.classList.toggle("nav-tab-active",active);tab.setAttribute("aria-selected",active?"true":"false");tab.setAttribute("tabindex",active?"0":"-1");});panels.forEach(function(panel){var active=panel.getAttribute("data-wp-settings-panel")===panelId;panel.hidden=!active;});};tabs.forEach(function(tab){tab.addEventListener("click",function(event){event.preventDefault();activate(tab.getAttribute("data-wp-settings-tab")||"");});});activate(tabs[0].getAttribute("data-wp-settings-tab")||"");});})();</script>';
    }

    /**
     * Render section description.
     *
    * @param array{description?: string} $args Section callback args.
     *
     * @return void
     */
    public function renderSection(array $args): void
    {
        if (!empty($args['description'])) {
            echo '<p>' . \esc_html((string) $args['description']) . '</p>';
        }
    }

    /**
     * Render a field control.
     *
    * @param array{id: string} $args Field callback args.
     *
     * @return void
     */
    public function renderField(array $args): void
    {
        $fieldId = (string) $args['id'];

        if (!isset($this->fields[$fieldId])) {
            return;
        }

        $field = $this->fields[$fieldId];
        $value = $this->getStoredValue($field);

        switch ($field['type']) {
            case 'checkbox':
                echo '<label for="' . \esc_attr($fieldId) . '">';
                echo '<input type="checkbox" id="' . \esc_attr($fieldId) . '" name="' . \esc_attr($fieldId) . '" value="1" ' . \checked(1, (int) $value, false) . ' />';
                echo ' ' . \esc_html($field['label']);
                echo '</label>';
                break;

            case 'textarea':
                echo '<textarea class="large-text" rows="6" id="' . \esc_attr($fieldId) . '" name="' . \esc_attr($fieldId) . '">' . \esc_textarea((string) $value) . '</textarea>';
                break;

            case 'select':
                echo '<select id="' . \esc_attr($fieldId) . '" name="' . \esc_attr($fieldId) . '">';
                foreach ($field['choices'] as $choiceValue => $choiceLabel) {
                    echo '<option value="' . \esc_attr((string) $choiceValue) . '" ' . \selected((string) $value, (string) $choiceValue, false) . '>' . \esc_html((string) $choiceLabel) . '</option>';
                }
                echo '</select>';
                break;

            case 'radio':
                foreach ($field['choices'] as $choiceValue => $choiceLabel) {
                    echo '<label style="display:block;margin-bottom:4px;">';
                    echo '<input type="radio" name="' . \esc_attr($fieldId) . '" value="' . \esc_attr((string) $choiceValue) . '" ' . \checked((string) $value, (string) $choiceValue, false) . ' /> ';
                    echo \esc_html((string) $choiceLabel);
                    echo '</label>';
                }
                break;

            case 'page-dropdown':
                \wp_dropdown_pages([
                    'name' => $fieldId,
                    'id' => $fieldId,
                    'echo' => 1,
                    'show_option_none' => \__('Select a page'),
                    'option_none_value' => '0',
                    'selected' => (int) $value,
                ]);
                break;

            case 'url':
                $this->renderTextInput($fieldId, $value, 'url');
                break;

            case 'color':
                $this->renderTextInput($fieldId, $value, 'text', '#000000');
                break;

            case 'file':
            case 'image':
            case 'text':
            default:
                $this->renderTextInput($fieldId, $value);
                break;
        }

        if (!empty($field['description'])) {
            echo '<p class="description">' . \esc_html((string) $field['description']) . '</p>';
        }
    }

    /**
    * Sanitize value before persisting as option.
     *
     * @param string $fieldId Field id.
     * @param mixed  $value   Raw value.
     *
     * @return mixed
     */
    protected function sanitizeValue(string $fieldId, mixed $value): mixed
    {
        if (!isset($this->fields[$fieldId])) {
            return $value;
        }

        $field = $this->fields[$fieldId];

        if (!empty($field['sanitize_callback']) && \is_callable($field['sanitize_callback'])) {
            $value = \call_user_func($field['sanitize_callback'], $value);
        }

        return $value;
    }

    /**
     * Resolve stored value for current field.
     *
    * @param array{identifier: string, label: string, type: string, default: mixed, capability: string, description: string, choices: array<string, string>, section: string, sanitize_callback: callable|string|null} $field Field config.
     *
     * @return mixed
     */
    protected function getStoredValue(array $field): mixed
    {
        $id = (string) $field['identifier'];
        $default = $field['default'] ?? '';

        return \get_option($id, $default);
    }

    /**
     * Helper to register typed fields with shared defaults.
     *
     * @param string               $fieldType Field type.
     * @param string|callable|null $sanitizeCallback Sanitize callback.
     * @param string               $identifier Identifier.
     * @param string               $label Label.
     * @param mixed                $default Default value.
     * @param string               $capability Capability.
     * @param string               $description Description.
     * @param array<string,string> $choices Choices.
     *
     * @return self
     */
    protected function addTypedField(
        string $fieldType,
        callable|string|null $sanitizeCallback,
        string $identifier,
        string $label,
        mixed $default,
        string $capability,
        string $description,
        array $choices = []
    ): self {
        return $this->add(
            $identifier,
            $label,
            $fieldType,
            $default,
            $capability,
            $description,
            $choices,
            $sanitizeCallback
        );
    }

    /**
     * Shared renderer for simple text-like inputs.
     *
     * @param string $fieldId Field ID.
     * @param mixed  $value Value.
     * @param string $type Input type.
     * @param string $placeholder Placeholder text.
     *
     * @return void
     */
    protected function renderTextInput(string $fieldId, mixed $value, string $type = 'text', string $placeholder = ''): void
    {
        $placeholderAttribute = $placeholder ? ' placeholder="' . \esc_attr($placeholder) . '"' : '';

        echo '<input class="regular-text" type="' . \esc_attr($type) . '" id="' . \esc_attr($fieldId) . '" name="' . \esc_attr($fieldId) . '" value="' . \esc_attr((string) $value) . '"' . $placeholderAttribute . ' />';
    }
}
