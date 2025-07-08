<?php namespace hws_jewel_trak_importer;

// Hook to load custom JavaScript in wp-admin head
add_action('admin_head', __NAMESPACE__ . '\\activate_listeners');
add_action('wp_ajax_'.__NAMESPACE__.'_modify_wp_config_constants',  __NAMESPACE__ . '\\modify_wp_config_constants_handler');    
add_action('wp_ajax_'.__NAMESPACE__.'_execute_function',  __NAMESPACE__ . '\\handle_execute_function_ajax');
add_action('wp_ajax_nopriv_'.__NAMESPACE__.'_execute_function',  __NAMESPACE__ . '\\handle_execute_function_ajax');  // For non-logged in users (optional)
add_action('wp_ajax_'.__NAMESPACE__.'_toggle_snippet',   __NAMESPACE__ . '\\toggle_snippet');


function activate_listeners()
{?>
<script>
jQuery(document).ready(function($) {
    $('#<?php echo Config::$settings_page_html_id;?> .modify-wp-config').on('click', function(e) {
        e.preventDefault();

        const constant = $(this).data('constant');
        const value = $(this).data('value');
        const target = $(this).data('target');

        $.post(ajaxurl, {
            action: '<?php echo __NAMESPACE__; ?>_modify_wp_config_constants',
            constants: {
                [constant]: value
            }
        }, function(response) {
            if (response.success) {
                alert(response.data.message || 'Configuration updated successfully.');
                location.reload();
            } else {
                alert(response.data.message || 'Failed to update configuration.');
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error('AJAX Request Failed:', jqXHR, textStatus, errorThrown);
            alert('AJAX request failed: ' + textStatus + ', ' + errorThrown);
        });
    });
});
</script>
  <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Event handler for enabling auto-updates for all plugins
            $('#<?php echo Config::$settings_page_html_id;?> #enable-plugin-auto-updates').on('click', function(e) {
                e.preventDefault();

                $.post(ajaxurl, {
                    action: '<?php echo __NAMESPACE__; ?>_enable_plugin_auto_updates',

                }, function(response) {
                    if (response.success) {
                        alert('Auto updates for all plugins have been enabled.');
                        location.reload();
                    } else {
                        alert('Failed to enable auto updates for plugins: ' + response.data.message);
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    alert('AJAX request failed: ' + textStatus + ', ' + errorThrown);
                });
            });
        });
    </script>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Event handler for enabling WP Core auto-updates
            $('#<?php echo Config::$settings_page_html_id;?>r #enable-auto-updates').on('click', function(e) {
                e.preventDefault();

                $.post(ajaxurl, {
                    action: '<?php echo __NAMESPACE__; ?>_modify_wp_config_constants',

                    constants: {
                        'WP_AUTO_UPDATE_CORE': 'true'
                    }
                }, function(response) {
                    if (response.success) {
                        alert('Auto updates have been enabled.');
                        location.reload();
                    } else {
                        alert('Failed to enable auto updates: ' + response.data.message);
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    alert('AJAX request failed: ' + textStatus + ', ' + errorThrown);
                });
            });
        });
    </script>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#<?php echo Config::$settings_page_html_id;?> .fix-ram-issue').on('click', function(e) {
                e.preventDefault();

                $.post(ajaxurl, {

                    action: '<?php echo __NAMESPACE__; ?>_modify_wp_config_constants',
                      constants: {
                        'WP_MEMORY_LIMIT': '4000M' // Adding the constant to update
                    }
                }, function(response) {
                    console.log('Raw AJAX Response:', response); // Log the entire response
                    console.log('Data Object:', response.data);   // Log the data object to see what's inside

                    var message = response.data ? response.data.message : 'No message received';

                    if (response.success) {
                        alert(message);
                        location.reload();
                    } else {
                        alert(message);
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    alert('AJAX request failed: ' + textStatus + ', ' + errorThrown);
                    console.error('AJAX Request Failed:', jqXHR, textStatus, errorThrown); // Debugging: Log the failure details
                });
            });
        });
    </script> 



<script>
$ = jQuery;
$(document).ready(function($) {
    // Handle click event and AJAX request all in one function
    $('#<?php echo Config::$settings_page_html_id;?> .execute-function').on('click', function() {

        var methodName = $(this).data('method');  // Get the method name
        var state = $(this).data('state');  // Get the state
        var setting = $(this).data('setting');  // Get the setting name
        var variable = $(this).data('variable');  // Get the setting name
        var slug = $(this).data('slug');  // Get the setting name
        var post_type = $(this).data('post_type');  // Get the setting name
        var name = $(this).data('name');  // Get the setting name

     



        // Ensure methodName and setting are available
        if (methodName) {
         //   console.log('State passed:', state);  // Log the state for debugging
        //    console.log('Setting passed:', setting);  // Log the setting for debugging

            // Make the AJAX call to execute the function
            var dataToSend = {
                action: '<?php echo __NAMESPACE__; ?>_execute_function',  // The action to hook into on the server-side
                method: methodName,          // Pass the method name
                setting: setting,            // Pass the setting name
                state: state,                // Pass the state
                variable: variable,                 // Pass the variable
                slug:slug,
                post_type:post_type,
                name:name
            };

            jQuery.ajax({
                url: ajaxurl,  // WordPress provides this for AJAX calls in the admin area
                type: 'post',
                data: dataToSend,
                success: function(response) {
                    if (response.success) {
                        alert(methodName+' executed successfully: ' + response.data);
                    } else {
                        alert('Error for '+methodName+': ' + response.data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log('AJAX Error:', textStatus, errorThrown, jqXHR.responseText);
                    alert('An AJAX error occurred: ' + textStatus + ' - ' + errorThrown);
                }
            });
        } else alert('No method or setting provided.');

    });
});


  // When the DOM is ready
  jQuery(document).ready(function($) {
    function checkAndHide() {
      if ($('.snippet_active').length > 0) {
        $('#product-importer-message').hide();
      } else {
        $('#product-importer-message').show();
      }
    }

    // Run once on page load
    checkAndHide();

    // Watch for DOM changes: if `.snippet_active` gets added later
    const observer = new MutationObserver(checkAndHide);
    observer.observe(document.body, {
      attributes: true,
      childList: true,
      subtree: true
    });
  });






  // 1) Define your namespace string once
  var ns = '<?php echo __NAMESPACE__; ?>';

  // 2) Ensure the global namespace object exists
  window[ns] = window[ns] || {};

  // 3) Define toggleSnippet under that namespace
  window[ns].toggleSnippet = function(snippetId) {
    var isChecked = jQuery('#' + snippetId).prop('checked');

    jQuery.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
        action: ns + '_toggle_snippet',
        snippet_id: snippetId,
        enable: isChecked
      },
      success: function(response) {
        if (response.success) {
          //alert(response.data);
        var containerSelector = '#wrapper-' + snippetId + ' .snippet-message';
         jQuery(containerSelector).html(response.data)
        } else {
          alert('Error: ' + response.data);
        }
      },
      error: function(jqXHR, textStatus, errorThrown) {
        console.error('AJAX Error:', textStatus, errorThrown, jqXHR.responseText);
        alert('An AJAX error occurred: ' + textStatus + ' - ' + errorThrown);
      }
    });
  };

  // 4) On document ready, bind clicks to call your namespaced function
  jQuery(document).ready(function() {
    jQuery('#<?php echo __NAMESPACE__; ?> .modify-snippet-via-button').on('click', function(e) {
      e.preventDefault();
      e.stopImmediatePropagation();

      var snippetId = jQuery(this).data('snippet-id');
      var action    = jQuery(this).data('action'); // "enable" or "disable"
      alert("Action: " + action + " | Snippet ID: " + snippetId);

      if (!snippetId) {
        return;
      }

      // Toggle hidden checkbox state if needed
      var shouldEnable = (action === 'enable');
      jQuery('#' + snippetId).prop('checked', shouldEnable);

      // Call the function under our namespace
      window[ns].toggleSnippet(snippetId);
    });
  });



</script>


<?php }


if ( ! function_exists( __NAMESPACE__ . '\\toggle_snippet' ) ) {
    function toggle_snippet() {
       // $settings_snippets = get_settings_snippets();
       $settings_snippets = [];

       $snippets_acf = get_snippets("acf");
       $snippets_admin = get_snippets("admin");
       $snippets_non_admin = get_snippets("non_admin");


// Merge all three arrays into one
$all_snippets = array_merge($snippets_acf, $snippets_admin, $snippets_non_admin);


        // Retrieve the snippet ID and the enable/disable state from the AJAX request
        $snippet_id = sanitize_text_field($_POST['snippet_id']);
        $enable = filter_var($_POST['enable'], FILTER_VALIDATE_BOOLEAN);

        write_log("Toggle snippet called with ID: {$snippet_id}, enable: " . ($enable ? 'true' : 'false'));

        // Find the corresponding snippet and function
        foreach ($all_snippets as $snippet) {
            if ($snippet['id'] === $snippet_id) {
                // Get the current value from the database
                $current_value = get_option($snippet_id);
                write_log("Current value of '{$snippet_id}': " . var_export($current_value, true));

                // Ensure both current and new values are booleans for accurate comparison
                $current_value_bool = filter_var($current_value, FILTER_VALIDATE_BOOLEAN);

                // Only update if the value has actually changed
                if ($current_value_bool !== $enable) {
                    write_log("Attempting to update '{$snippet_id}' to " . ($enable ? 'true' : 'false'));

                    // Attempt the update
                    $updated = update_option($snippet_id, $enable);

                    // Log the result of the update attempt
                    if ($updated) {
                        write_log("Option '{$snippet_id}' updated successfully.");
                        //wp_send_json_success("Option '{$snippet_id}' updated successfully.");
                          if ($snippet_id === 'enable_product_importer') {
                                ob_start();
                                ?>
                                <div class="snippet-message">
                                  <?php if (get_option('enable_product_importer')) : ?>
                                    <div class="snippet_active" style="margin-top:20px; padding:10px; border:1px solid #4CAF50; background:#e8f5e9; color:#2e7d32;">
                                      <h3>Product Importer</h3>
                                      <p>The Product Importer is <strong>enabled</strong>.</p>
                                      <p>Run the import by visiting this URL (must be logged in as admin):</p>
                                      <a href="<?php echo esc_url(admin_url('?run_import=1')); ?>" target="_blank">
                                        <?php echo esc_html(admin_url('?run_import=1')); ?>
                                      </a>
                                    </div>
                                  <?php else : ?>
                                    <div class="snippet_active" style="margin-top:20px; padding:10px; border:1px solid #ccc; background:#f7f7f7; color:#555;">
                                      <p>The Product Importer is currently <strong>disabled</strong>. Enable it above to see the import URL.</p>
                                    </div>
                                  <?php endif; ?>
                                </div>
                                <?php
                                $html = ob_get_clean();
                                wp_send_json_success($html);

                              } elseif ($snippet_id === 'enable_product_importer_process_deletes') {
                                ob_start();
                                ?>
                                <div class="snippet-message">
                                  <?php if (get_option('enable_product_importer_process_deletes')) : ?>
                                    <div class="snippet_active" style="margin-top:20px; padding:10px; border:1px solid #4CAF50; background:#e8f5e9; color:#2e7d32;">
                                      <h3>Product Process Delete</h3>
                                      <p>The Product Importer process Delete is <strong>enabled</strong>.</p>
                                      <p>Run the Delete Process by visiting this URL (must be logged in as admin):</p>
                                      <a href="<?php echo esc_url(admin_url('?run_delete=1')); ?>" target="_blank">
                                        <?php echo esc_html(admin_url('?run_delete=1')); ?>
                                      </a>
                                    </div>
                                  <?php else : ?>
                                    <div class="snippet_active" style="margin-top:20px; padding:10px; border:1px solid #ccc; background:#f7f7f7; color:#555;">
                                      <p>The Product Importer process Delete is currently <strong>disabled</strong>. Enable it above to see the Delete Product URL.</p>
                                    </div>
                                  <?php endif; ?>
                                </div>
                                <?php
                                $html = ob_get_clean();
                                wp_send_json_success($html);

                              }
                                                        

                            else {
                                // For other snippets, send simple success message
                                wp_send_json_success("Option '{$snippet_id}' updated successfully.");
                            }
                    } else {
                        global $wpdb;
                        $db_error = $wpdb->last_error;
                        write_log("Failed to update option '{$snippet_id}'. Database error: {$db_error}");
                        wp_send_json_error("Failed to update option '{$snippet_id}'. Database error: {$db_error}");
                    }
                } else {
                    write_log("No update required for '{$snippet_id}'. Current value is the same as the new value.");
                    wp_send_json_error("No update required for '{$snippet_id}'. Current value is the same.");
                }

                exit; // Stop further processing once the correct snippet is found
            }
        }

        write_log("Invalid snippet ID: {$snippet_id}");
        wp_send_json_error("Invalid snippet ID: {$snippet_id}");

        wp_die(); // Ensure proper termination of the script
    }
} else write_log("Warning: " . __NAMESPACE__ . "/smp_verified_profiles/toggle_snippet function is already declared", true);



   


function modify_wp_config_constants_handler() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    $constants = isset($_POST['constants']) ? $_POST['constants'] : [];
    if (empty($constants)) {
        wp_send_json_error(['message' => 'No constants provided']);
    }

    $result = modify_wp_config_constants($constants);

    if ($result['status']) {
        wp_send_json_success(['message' => $result['message']]);
    } else {
        wp_send_json_error(['message' => $result['message']]);
    }
}


function handle_execute_function_ajax() {
    // Verify if the method parameter is passed and is not empty
    if (isset($_POST['method']) && !empty($_POST['method'])) {
        $method_name = sanitize_text_field($_POST['method']);

        $variable = "";
        if(isset($_POST['variable']))
        $variable = $_POST['variable'];
        // Determine the correct namespace
        $namespace =  __NAMESPACE__ ."";
        $fully_qualified_function_name = $namespace . '\\' . $method_name;
        write_log("handle_execute_function_ajax - Method name passed: " . $fully_qualified_function_name, true);
        // Get the state if passed
        $state = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : null;

        // Check if the function exists with the namespace
        if (function_exists($fully_qualified_function_name)) {
            // Execute the function with both the setting and state

            if($method_name == "toggle_php_ini_value")
            $response = call_user_func($fully_qualified_function_name,$variable, $state);
            else  if($method_name == "create_category_for_post_type")
          $response = call_user_func($fully_qualified_function_name,$_POST['name'],$_POST['slug'],$_POST['post_type'] );
else
            $response = call_user_func($fully_qualified_function_name, $state);
        
          
            // Send a success response with the result of the function execution
            wp_send_json_success($response);
        } else {
            write_log("The function does not exist: " . $fully_qualified_function_name, true);
            wp_send_json_error('The function does not exist.');
        }
    } else {
        wp_send_json_error('No method name provided.');
    }

    wp_die();  // This is required to properly terminate the script when doing AJAX in WordPress
}
?>