<?php
/**
 * Component Types Registry
 * Defines all available component types with their schemas
 */

namespace Modular\Components;

class ComponentTypes {
    public static function getTypes() {
        return [
            // Basic Fields
            'text' => [
                'label' => 'Text',
                'icon' => 'text',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'placeholder' => ['type' => 'string'],
                    'default' => ['type' => 'string'],
                    'maxlength' => ['type' => 'integer'],
                    'required' => ['type' => 'boolean', 'default' => false]
                ],
                'storage' => 'string',
                'validation' => ['maxlength', 'required']
            ],
            'textarea' => [
                'label' => 'Textarea',
                'icon' => 'textarea',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'rows' => ['type' => 'integer', 'default' => 4],
                    'maxlength' => ['type' => 'integer'],
                    'required' => ['type' => 'boolean', 'default' => false]
                ],
                'storage' => 'text',
                'validation' => ['maxlength', 'required']
            ],
            'wysiwyg' => [
                'label' => 'WYSIWYG Editor',
                'icon' => 'editor',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'toolbar' => ['type' => 'string', 'default' => 'full'],
                    'media_upload' => ['type' => 'boolean', 'default' => true]
                ],
                'storage' => 'text',
                'validation' => []
            ],
            'title' => [
                'label' => 'Title',
                'icon' => 'title',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true, 'default' => 'title'],
                    'label' => ['type' => 'string', 'default' => 'Title'],
                    'required' => ['type' => 'boolean', 'default' => true]
                ],
                'storage' => 'string',
                'validation' => ['required']
            ],
            'slug' => [
                'label' => 'Slug',
                'icon' => 'link',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true, 'default' => 'slug'],
                    'label' => ['type' => 'string', 'default' => 'Slug'],
                    'auto_generate' => ['type' => 'boolean', 'default' => true],
                    'source_field' => ['type' => 'string', 'default' => 'title']
                ],
                'storage' => 'string',
                'validation' => ['slug_format']
            ],
            'excerpt' => [
                'label' => 'Excerpt',
                'icon' => 'excerpt',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true, 'default' => 'excerpt'],
                    'label' => ['type' => 'string', 'default' => 'Excerpt'],
                    'maxlength' => ['type' => 'integer', 'default' => 160]
                ],
                'storage' => 'text',
                'validation' => ['maxlength']
            ],
            
            // Media Fields
            'image' => [
                'label' => 'Image',
                'icon' => 'image',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'multiple' => ['type' => 'boolean', 'default' => false],
                    'min_width' => ['type' => 'integer'],
                    'min_height' => ['type' => 'integer'],
                    'max_width' => ['type' => 'integer'],
                    'max_height' => ['type' => 'integer'],
                    'allowed_types' => ['type' => 'array', 'default' => ['jpg', 'jpeg', 'png', 'gif', 'webp']],
                    'crop' => ['type' => 'boolean', 'default' => false],
                    'focal_point' => ['type' => 'boolean', 'default' => false]
                ],
                'storage' => 'integer', // Media ID
                'validation' => ['file_type', 'dimensions']
            ],
            'gallery' => [
                'label' => 'Gallery',
                'icon' => 'gallery',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'min_images' => ['type' => 'integer', 'default' => 0],
                    'max_images' => ['type' => 'integer'],
                    'order' => ['type' => 'boolean', 'default' => true]
                ],
                'storage' => 'json', // Array of media IDs
                'validation' => ['min_items', 'max_items']
            ],
            'file' => [
                'label' => 'File',
                'icon' => 'file',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'allowed_types' => ['type' => 'array'],
                    'max_size' => ['type' => 'integer'] // in bytes
                ],
                'storage' => 'integer', // Media ID
                'validation' => ['file_type', 'file_size']
            ],
            
            // Selection Fields
            'select' => [
                'label' => 'Select',
                'icon' => 'select',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'options' => ['type' => 'array', 'required' => true],
                    'multiple' => ['type' => 'boolean', 'default' => false],
                    'required' => ['type' => 'boolean', 'default' => false]
                ],
                'storage' => 'string',
                'validation' => ['required', 'in_options']
            ],
            'radio' => [
                'label' => 'Radio',
                'icon' => 'radio',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'options' => ['type' => 'array', 'required' => true],
                    'required' => ['type' => 'boolean', 'default' => false]
                ],
                'storage' => 'string',
                'validation' => ['required', 'in_options']
            ],
            'checkbox' => [
                'label' => 'Checkbox',
                'icon' => 'checkbox',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'options' => ['type' => 'array', 'required' => true],
                    'required' => ['type' => 'boolean', 'default' => false]
                ],
                'storage' => 'json', // Array of selected values
                'validation' => ['required', 'in_options']
            ],
            
            // Number Fields
            'number' => [
                'label' => 'Number',
                'icon' => 'number',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'min' => ['type' => 'number'],
                    'max' => ['type' => 'number'],
                    'step' => ['type' => 'number', 'default' => 1],
                    'required' => ['type' => 'boolean', 'default' => false]
                ],
                'storage' => 'decimal',
                'validation' => ['min', 'max', 'required']
            ],
            'range' => [
                'label' => 'Range',
                'icon' => 'range',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'min' => ['type' => 'number', 'default' => 0],
                    'max' => ['type' => 'number', 'default' => 100],
                    'step' => ['type' => 'number', 'default' => 1]
                ],
                'storage' => 'decimal',
                'validation' => ['min', 'max']
            ],
            
            // Boolean
            'boolean' => [
                'label' => 'Boolean/Toggle',
                'icon' => 'toggle',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'default' => ['type' => 'boolean', 'default' => false]
                ],
                'storage' => 'boolean',
                'validation' => []
            ],
            
            // Date/Time
            'date' => [
                'label' => 'Date',
                'icon' => 'calendar',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'format' => ['type' => 'string', 'default' => 'Y-m-d'],
                    'required' => ['type' => 'boolean', 'default' => false]
                ],
                'storage' => 'date',
                'validation' => ['date_format', 'required']
            ],
            'time' => [
                'label' => 'Time',
                'icon' => 'clock',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'format' => ['type' => 'string', 'default' => 'H:i'],
                    'required' => ['type' => 'boolean', 'default' => false]
                ],
                'storage' => 'time',
                'validation' => ['time_format', 'required']
            ],
            'datetime' => [
                'label' => 'Date & Time',
                'icon' => 'calendar-clock',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'timezone' => ['type' => 'string', 'default' => 'UTC'],
                    'required' => ['type' => 'boolean', 'default' => false]
                ],
                'storage' => 'datetime',
                'validation' => ['datetime_format', 'required']
            ],
            
            // Advanced Fields
            'repeater' => [
                'label' => 'Repeater',
                'icon' => 'repeat',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'sub_fields' => ['type' => 'array', 'required' => true],
                    'min_rows' => ['type' => 'integer', 'default' => 0],
                    'max_rows' => ['type' => 'integer']
                ],
                'storage' => 'json',
                'validation' => ['min_rows', 'max_rows']
            ],
            'group' => [
                'label' => 'Group',
                'icon' => 'group',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'sub_fields' => ['type' => 'array', 'required' => true],
                    'layout' => ['type' => 'string', 'default' => 'block'] // block, table, row
                ],
                'storage' => 'json',
                'validation' => []
            ],
            'relationship' => [
                'label' => 'Relationship',
                'icon' => 'link',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'post_type' => ['type' => 'string', 'required' => true],
                    'multiple' => ['type' => 'boolean', 'default' => false],
                    'filters' => ['type' => 'array']
                ],
                'storage' => 'json', // Array of post IDs
                'validation' => ['post_exists']
            ],
            'post_object' => [
                'label' => 'Post Object',
                'icon' => 'post',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'post_type' => ['type' => 'string', 'required' => true],
                    'multiple' => ['type' => 'boolean', 'default' => false]
                ],
                'storage' => 'integer', // Post ID
                'validation' => ['post_exists']
            ],
            'taxonomy' => [
                'label' => 'Taxonomy',
                'icon' => 'tags',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'taxonomy' => ['type' => 'string', 'required' => true],
                    'field_type' => ['type' => 'string', 'default' => 'select'], // select, checkbox, multi_select
                    'multiple' => ['type' => 'boolean', 'default' => false]
                ],
                'storage' => 'json', // Array of term IDs
                'validation' => ['term_exists']
            ],
            'user' => [
                'label' => 'User',
                'icon' => 'user',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'role' => ['type' => 'string'],
                    'multiple' => ['type' => 'boolean', 'default' => false]
                ],
                'storage' => 'integer', // User ID
                'validation' => ['user_exists']
            ],
            
            // Special Fields
            'map' => [
                'label' => 'Map/Location',
                'icon' => 'map',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'center_lat' => ['type' => 'number', 'default' => 0],
                    'center_lng' => ['type' => 'number', 'default' => 0],
                    'zoom' => ['type' => 'integer', 'default' => 10]
                ],
                'storage' => 'json', // {lat, lng, address}
                'validation' => ['coordinates']
            ],
            'embed' => [
                'label' => 'Embed (oEmbed)',
                'icon' => 'embed',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'preview_size' => ['type' => 'string', 'default' => 'responsive']
                ],
                'storage' => 'text', // oEmbed URL or HTML
                'validation' => ['url']
            ],
            'video' => [
                'label' => 'Video',
                'icon' => 'video',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'sources' => ['type' => 'array', 'default' => ['youtube', 'vimeo', 'self']]
                ],
                'storage' => 'json', // {type, url, embed_code}
                'validation' => ['video_url']
            ],
            'color' => [
                'label' => 'Color Picker',
                'icon' => 'color',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'format' => ['type' => 'string', 'default' => 'hex'] // hex, rgb, rgba
                ],
                'storage' => 'string',
                'validation' => ['color_format']
            ],
            'rating' => [
                'label' => 'Rating/Stars',
                'icon' => 'star',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'max' => ['type' => 'integer', 'default' => 5],
                    'step' => ['type' => 'number', 'default' => 0.5]
                ],
                'storage' => 'decimal',
                'validation' => ['min', 'max']
            ],
            'code' => [
                'label' => 'Code Block',
                'icon' => 'code',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'language' => ['type' => 'string'],
                    'theme' => ['type' => 'string', 'default' => 'default']
                ],
                'storage' => 'text',
                'validation' => []
            ],
            'seo' => [
                'label' => 'SEO Block',
                'icon' => 'seo',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true, 'default' => 'seo'],
                    'label' => ['type' => 'string', 'default' => 'SEO'],
                    'fields' => ['type' => 'array', 'default' => ['meta_title', 'meta_description', 'canonical', 'robots']]
                ],
                'storage' => 'json',
                'validation' => []
            ],
            'html' => [
                'label' => 'Custom HTML',
                'icon' => 'html',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'sanitize' => ['type' => 'boolean', 'default' => true]
                ],
                'storage' => 'text',
                'validation' => ['xss_check']
            ],
            'json' => [
                'label' => 'JSON Field',
                'icon' => 'json',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'schema' => ['type' => 'object'] // JSON Schema for validation
                ],
                'storage' => 'json',
                'validation' => ['json_schema']
            ],
            'calculated' => [
                'label' => 'Calculated Field',
                'icon' => 'calculator',
                'schema' => [
                    'name' => ['type' => 'string', 'required' => true],
                    'label' => ['type' => 'string', 'required' => true],
                    'expression' => ['type' => 'string', 'required' => true],
                    'readonly' => ['type' => 'boolean', 'default' => true]
                ],
                'storage' => 'string',
                'validation' => []
            ],
            
            // Content Blocks
            'hero' => [
                'label' => 'Hero Block',
                'icon' => 'hero',
                'schema' => [
                    'title' => ['type' => 'string', 'required' => true],
                    'subtitle' => ['type' => 'string'],
                    'background_image' => ['type' => 'integer'],
                    'cta_text' => ['type' => 'string'],
                    'cta_link' => ['type' => 'string']
                ],
                'storage' => 'json',
                'validation' => []
            ],
            'cta' => [
                'label' => 'Call to Action',
                'icon' => 'cta',
                'schema' => [
                    'title' => ['type' => 'string', 'required' => true],
                    'description' => ['type' => 'text'],
                    'button_text' => ['type' => 'string', 'required' => true],
                    'button_link' => ['type' => 'string', 'required' => true],
                    'style' => ['type' => 'string', 'default' => 'primary']
                ],
                'storage' => 'json',
                'validation' => []
            ],
            'quote' => [
                'label' => 'Quote',
                'icon' => 'quote',
                'schema' => [
                    'quote' => ['type' => 'text', 'required' => true],
                    'author' => ['type' => 'string'],
                    'author_title' => ['type' => 'string'],
                    'author_image' => ['type' => 'integer']
                ],
                'storage' => 'json',
                'validation' => []
            ],
            'testimonial' => [
                'label' => 'Testimonial',
                'icon' => 'testimonial',
                'schema' => [
                    'testimonial' => ['type' => 'text', 'required' => true],
                    'author_name' => ['type' => 'string', 'required' => true],
                    'author_role' => ['type' => 'string'],
                    'author_company' => ['type' => 'string'],
                    'author_image' => ['type' => 'integer'],
                    'rating' => ['type' => 'number', 'min' => 0, 'max' => 5]
                ],
                'storage' => 'json',
                'validation' => []
            ],
            'feature_list' => [
                'label' => 'Feature List',
                'icon' => 'features',
                'schema' => [
                    'title' => ['type' => 'string'],
                    'features' => ['type' => 'array', 'required' => true],
                    'layout' => ['type' => 'string', 'default' => 'grid'] // grid, list
                ],
                'storage' => 'json',
                'validation' => []
            ],
            'pricing_table' => [
                'label' => 'Pricing Table',
                'icon' => 'pricing',
                'schema' => [
                    'title' => ['type' => 'string'],
                    'plans' => ['type' => 'array', 'required' => true],
                    'highlighted' => ['type' => 'integer']
                ],
                'storage' => 'json',
                'validation' => []
            ],
            'gallery_grid' => [
                'label' => 'Gallery Grid',
                'icon' => 'gallery-grid',
                'schema' => [
                    'images' => ['type' => 'array', 'required' => true],
                    'columns' => ['type' => 'integer', 'default' => 3],
                    'gap' => ['type' => 'string', 'default' => 'medium']
                ],
                'storage' => 'json',
                'validation' => []
            ],
            'slider' => [
                'label' => 'Slider',
                'icon' => 'slider',
                'schema' => [
                    'slides' => ['type' => 'array', 'required' => true],
                    'autoplay' => ['type' => 'boolean', 'default' => false],
                    'interval' => ['type' => 'integer', 'default' => 5000],
                    'navigation' => ['type' => 'boolean', 'default' => true]
                ],
                'storage' => 'json',
                'validation' => []
            ],
            'tabs' => [
                'label' => 'Tabs',
                'icon' => 'tabs',
                'schema' => [
                    'tabs' => ['type' => 'array', 'required' => true],
                    'style' => ['type' => 'string', 'default' => 'default']
                ],
                'storage' => 'json',
                'validation' => []
            ],
            'accordion' => [
                'label' => 'Accordion',
                'icon' => 'accordion',
                'schema' => [
                    'items' => ['type' => 'array', 'required' => true],
                    'allow_multiple' => ['type' => 'boolean', 'default' => false]
                ],
                'storage' => 'json',
                'validation' => []
            ],
            'table' => [
                'label' => 'Table',
                'icon' => 'table',
                'schema' => [
                    'headers' => ['type' => 'array', 'required' => true],
                    'rows' => ['type' => 'array', 'required' => true],
                    'stripe' => ['type' => 'boolean', 'default' => true]
                ],
                'storage' => 'json',
                'validation' => []
            ],
            'timeline' => [
                'label' => 'Timeline',
                'icon' => 'timeline',
                'schema' => [
                    'events' => ['type' => 'array', 'required' => true],
                    'orientation' => ['type' => 'string', 'default' => 'vertical']
                ],
                'storage' => 'json',
                'validation' => []
            ],
            'contact_form' => [
                'label' => 'Contact Form Block',
                'icon' => 'form',
                'schema' => [
                    'form_id' => ['type' => 'integer', 'required' => true],
                    'title' => ['type' => 'string'],
                    'description' => ['type' => 'text']
                ],
                'storage' => 'json',
                'validation' => ['form_exists']
            ],
            'related_posts' => [
                'label' => 'Related Posts',
                'icon' => 'related',
                'schema' => [
                    'post_type' => ['type' => 'string', 'required' => true],
                    'count' => ['type' => 'integer', 'default' => 3],
                    'relation_type' => ['type' => 'string'],
                    'taxonomy' => ['type' => 'string']
                ],
                'storage' => 'json',
                'validation' => []
            ]
        ];
    }
    
    public static function getType($type) {
        $types = self::getTypes();
        return $types[$type] ?? null;
    }
}


