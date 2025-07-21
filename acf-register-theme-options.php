<?php namespace hws_jewel_trak_importer;

function enable_acf_theme_options() {









	acf_add_local_field_group( array(
	'key' => 'group_68633c8e28585',
	'title' => 'JewelTrak Options',
	'fields' => array(
		array(
			'key' => 'field_68633c8e954fa',
			'label' => 'FTP Credentials',
			'name' => 'ftp_credentials',
			'aria-label' => '',
			'type' => 'group',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'layout' => 'block',
			'sub_fields' => array(
				array(
					'key' => 'field_68633cb8cf118',
					'label' => 'Host',
					'name' => 'host',
					'aria-label' => '',
					'type' => 'text',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'default_value' => '',
					'maxlength' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
				),
				array(
					'key' => 'field_68633cbdcf119',
					'label' => 'Password',
					'name' => 'password',
					'aria-label' => '',
					'type' => 'text',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'default_value' => '',
					'maxlength' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
				),
				array(
					'key' => 'field_68633cc3cf11a',
					'label' => 'Username',
					'name' => 'username',
					'aria-label' => '',
					'type' => 'text',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'default_value' => '',
					'maxlength' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
				),
				array(
					'key' => 'field_68633cd4cf11b',
					'label' => 'Port',
					'name' => 'port',
					'aria-label' => '',
					'type' => 'text',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'default_value' => '',
					'maxlength' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
				),
				array(
					'key' => 'field_68633ce8f6982',
					'label' => 'Directory',
					'name' => 'directory',
					'aria-label' => '',
					'type' => 'text',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'default_value' => '',
					'maxlength' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
				),
			),
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'options_page',
				'operator' => '==',
				'value' => 'hws-jewel-trak-importer-theme-options',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => true,
	'description' => '',
	'show_in_rest' => 0,
) );


	acf_add_local_field_group( array(
	'key' => 'group_68758203b9812',
	'title' => 'Test',
	'fields' => array(
		array(
			'key' => 'field_68758204085e5',
			'label' => 'Product Custom Fields',
			'name' => 'product_custom_fields',
			'aria-label' => '',
			'type' => 'repeater',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'layout' => 'table',
			'pagination' => 0,
			'min' => 0,
			'max' => 0,
			'collapsed' => '',
			'button_label' => 'Add Row',
			'rows_per_page' => 20,
			'sub_fields' => array(
				array(
					'key' => 'field_6875821d085e6',
					'label' => 'Website Display Header',
					'name' => 'display_header',
					'aria-label' => '',
					'type' => 'text',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'default_value' => '',
					'maxlength' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
					'parent_repeater' => 'field_68758204085e5',
				),
				array(
					'key' => 'field_6875828d085e7',
					'label' => 'CSV Header',
					'name' => 'csv_header',
					'aria-label' => '',
					'type' => 'text',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'default_value' => '',
					'maxlength' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
					'parent_repeater' => 'field_68758204085e5',
				),
				
				
						array(
					'key' => 'field_6875cd8fb46db',
					'label' => 'Type',
					'name' => 'type',
					'aria-label' => '',
					'type' => 'select',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'choices' => array(
						'text' => 'Text',
						'select' => 'Select',
					),
					'default_value' => false,
					'return_format' => 'value',
					'multiple' => 0,
					'allow_null' => 0,
					'ui' => 0,
					'ajax' => 0,
					'placeholder' => '',
					'parent_repeater' => 'field_68758204085e5',
				),
                array(
                    'key'               => 'field_6875e5b3c9012',
                    'label'             => 'ID',
                    'name'              => 'id',
                    'aria-label'        => '',
                    'type'              => 'text',
                    'instructions'      => '',
                    'required'          => 0,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                    'default_value'     => '',
                    'maxlength'         => '',
                    'placeholder'       => '',
                    'prepend'           => '',
                    'append'            => '',
                    'parent_repeater'   => 'field_68758204085e5',
                ),
                
					array(
					'key' => 'field_6875d20122032',
					'label' => 'Visible',
					'name' => 'visible',
					'aria-label' => '',
					'type' => 'true_false',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'message' => '',
					'default_value' => 0,
					'ui' => 0,
					'ui_on_text' => '',
					'ui_off_text' => '',
					'parent_repeater' => 'field_6875a8204085e5',
				),
			
			
			),
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'options_page',
				'operator' => '==',
				'value' => 'hws-jewel-trak-importer-theme-options',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => true,
	'description' => '',
	'show_in_rest' => 0,
) );


// Inside your enable_acf_theme_options() function, add this:
acf_add_local_field_group( array(
    'key'                   => 'group_general_settings',
    'title'                 => 'General Settings',
    'fields'                => array(
        array(
            'key'               => 'field_purchase_price_column',
            'label'             => 'PurchasePrice Column',
            'name'              => 'purchase_price_column',
            'type'              => 'text',
            'instructions'      => '',
            'required'          => 0,
            'conditional_logic' => 0,
            'wrapper'           => array(
                'width' => '',
                'class' => '',
                'id'    => '',
            ),
            'default_value'     => '',
            'placeholder'       => '',
            'prepend'           => '',
            'append'            => '',
            'maxlength'         => '',
        ),
    ),
    'location'              => array(
        array(
            array(
                'param'    => 'options_page',
                'operator' => '==',
                'value'    => 'hws-jewel-trak-importer-theme-options',
            ),
        ),
    ),
    'menu_order'            => 0,
    'position'              => 'normal',
    'style'                 => 'default',
    'label_placement'       => 'top',
    'instruction_placement' => 'label',
    'hide_on_screen'        => '',
    'active'                => true,
    'description'           => '',
    'show_in_rest'          => 0,
) );



	acf_add_options_page( array(
	'page_title' => 'JewelTrak Theme Options',
	'menu_slug' => 'hws-jewel-trak-importer-theme-options',
	'redirect' => false,
) );

    }

