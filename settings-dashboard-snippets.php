<?php namespace hws_jewel_trak_importer;


    function display_settings_snippets() {
        add_action('admin_init', 'acf_form_init');
    
        function acf_form_init() {
            acf_form_head();
        }
        ?>
    

    <style>
        .panel-settings-snippets {
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            margin-bottom: 20px;
            background-color: #f7f7f7;
            padding: 10px 15px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            font-size: 14px;
        }

        .panel-settings-snippets .panel-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .panel-settings-snippets .panel-content {
            padding: 10px 0;
        }

        .panel-settings-snippets ul {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }

        .panel-settings-snippets li {
            padding: 1px 0;
            font-size: 12px;
            color: #888;
        }

        .panel-settings-snippets input[type="checkbox"] {
            margin-right: 10px;
        }

        .panel-settings-snippets label {
            font-size: 13px;
            color: #555;
        }

        .panel-settings-snippets small {
            display: block;
            margin-top: 3px;
            color: #777;
            font-size: 12px;
        }

        .snippet-item {
            margin-bottom: 12px;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #dcdcdc;
            background-color: #fff;
        }
    </style>
        <!-- Snippets Status Panel -->
        <div class="panel panel-settings-snippets">
            <h2 class="panel-title">Snippets</h2>
            <div class="panel-content">
                <h3>Active Snippets:</h3>
                <div style="margin-left: 15px; color: green;">
                    <?php
                    // Initialize an array to store active snippets
                    $active_snippets = [];
                  // $settings_snippets = get_settings_snippets();
                  //$settings_snippets=[];
                  $snippets_acf = get_snippets("acf");
                  $snippets_admin = get_snippets("admin");
                  $snippets_non_admin = get_snippets("non_admin");
                  $settings_snippets = array_merge(
                      get_snippets("acf"),
                      get_snippets("admin"),
                      get_snippets("non_admin")
                    );


                    // Iterate through the snippets and check which ones are active
                    foreach ($settings_snippets as $snippet) {
                        $is_enabled = get_option($snippet['id'], false);
                        if ($is_enabled) {
                            $active_snippets[] = $snippet['name']; // Add active snippet names to the array
                        }
                    }
    
                        // Display active snippets or a message if none are found
                if (!empty($active_snippets)) {
                    echo "<ul>";
                    foreach ($active_snippets as $snippet_name) {
                        echo "<li>&#x2705; {$snippet_name}</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p>No active snippets found.</p>";
                }
                    ?>
                </div>
    
                <!-- Snippet Actions and Status -->
                <div style="margin-bottom: 15px;">
                    <h3>Available Snippets:</h3>
                    <div style="margin-left: 15px;">
                        <?php


// Merge all three arrays into one
$all_snippets = array_merge($snippets_acf, $snippets_admin, $snippets_non_admin);

// Loop through every snippet and render its checkbox
foreach ($all_snippets as $snippet) {
    // Get the current state of the option from the database
    $is_enabled = get_option($snippet['id'], false);
    $info_html  = isset($snippet['info']) ? $snippet['info'] : '';

    // Determine if the checkbox should be checked
    $checked = $is_enabled ? 'checked' : '';

    // Output the checkbox, label, and details
    echo "<div style='color: #555; margin-bottom: 10px;' id='wrapper-{$snippet['id']}'>
    <input
        type='checkbox'
        id='{$snippet['id']}'
        onclick='window." . __NAMESPACE__ . ".toggleSnippet(\"{$snippet['id']}\")'
        {$checked}
    >
    <label for='{$snippet['id']}'>
        {$snippet['name']} – <em>{$snippet['description']}</em><br>
        <small><strong>Details:</strong><br>{$info_html}</small>
    </label>
    <div class='snippet-message'></div>
  </div>";}

  

                        ?>
                        <div id="product-importer-message">
                         <?php if (get_option('enable_product_importer')) : ?>
                <div style="margin-top:20px; padding:10px; border:1px solid #4CAF50; background:#e8f5e9; color:#2e7d32;">
                    <h3>Product Importer</h3>
                    <p>The Product Importer is <strong>enabled</strong>.</p>
                    <p>Run the import by visiting this URL (must be logged in as admin):</p>
                    <a href="<?php echo esc_url(admin_url('?run_import=1')); ?>" target="_blank">
                        <?php echo esc_html(admin_url('?run_import=1')); ?>
                    </a>
                </div>
            <?php else : ?>
                <div style="margin-top:20px; padding:10px; border:1px solid #ccc; background:#f7f7f7; color:#555;">
                    <p>The Product Importer is currently <strong>disabled</strong>. Enable it above to see the import URL.</p>
                </div>
            <?php endif; ?>
</div>
                    </div>
                </div>
            </div>
        </div>



  
    
    <?php }
    
?>