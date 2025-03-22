<?php
/*
 * This plugin is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */
if (!defined('ABSPATH')) exit;
// Handle form submission (saving updates)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_prompts_v2'])) {
    check_admin_referer('save_story_prompts', 'story_prompts_nonce');
    $updated_prompts = isset($_POST['prompts']) ? json_decode(stripslashes($_POST['prompts']), true) : [];
    update_option('ai_story_prompts', json_encode($updated_prompts, JSON_PRETTY_PRINT));
    echo '<div class="updated"><p>✅ Prompts saved successfully!</p></div>';
}
// Reading the current settings
$raw_json = get_option('ai_story_prompts', '{}');
$settings = json_decode($raw_json, true);
$prompts = $settings['prompts'] ?? [];
$default_settings = $settings['default_settings'] ?? [];
?>
<div class="wrap">
<div class="ai-storymaker-settings" >

<h2>Prompt General Settings</h2>
<ul>
    <li>- You can set the model and system content that will be used to generate stories.</li>
    <li>- The system content will be added to the end of each prompt you send.</li>

</ul>
    <?php wp_nonce_field('save_story_prompts', 'story_prompts_nonce'); ?>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
        <tr>
            <th width="15%">Model</th>
            <th>General Behaviour   </th>
        </tr>
        </thead>
        <tr>
            <td>
                <select name="model" id="model">
                    <option value="gpt-4o-mini" <?php selected($default_settings['model'], 'gpt-4o-mini'); ?>>GPT-4o Mini</option>
                    <option value="gpt-4o" <?php selected($default_settings['model'], 'gpt-4o'); ?>>GPT-4o</option>
                    <option value="gpt-4o-large" <?php selected($default_settings['model'], 'gpt-4o-large'); ?>>GPT-4o Large</option>
                </select>

            </td>
            <td><textarea name="system_content" id="system_content" rows="5" style="width: 100%;"><?php echo esc_textarea($default_settings['system_content']); ?></textarea></td>

        </tr>
    </table>
    <hr>
    <h2>Manage AI Story Prompts</h2>
    <ul>
        <li>- You can add multiple prompts for multiple stories.</li>
        <li>- Each story can have a category, you can manage categories from Settings->Writing option->Categories</li>  
        <li>- Each prompt can have a category, photos, and be active or inactive
        <li>- Active prompts will be used to generate stories, while inactive prompts will be ignored.</li>
        <li>- Click the "Add New Prompt" button to create a new prompt. Click the "Delete" button to remove a prompt.</li>
        <li>- Click "Save Changes" to update the prompts list.</li>
        <li>- Click "Generate Story" to create a new story based on the prompts.</li>
    </ul>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th width="70%">Prompt Text</th>
                <th width="15%">Category</th>
                <th width="5%">Photos</th>
                <th width="5%">Active</th>
                <th width="5%">Actions</th>
            </tr>
        </thead>
        <tbody id="prompt-list">
            <?php foreach ($prompts as $index => $prompt) : ?>
                <tr data-index="<?php echo $index; ?>">
                    <td contenteditable="true" class="editable" data-field="text"><?php echo esc_html($prompt['text'] ?? ''); ?></td>
                    <td contenteditable="true"  data-field="category">
                        <?php
                        $categories = get_categories(array('hide_empty' => false));
                        ?>
                        <select name="category">
                            <?php foreach ($categories as $cat) : ?>
                                <option value="<?php echo esc_attr($cat->slug); ?>" <?php selected($prompt['category'] ?? '', $cat->slug); ?>>
                                    <?php echo esc_html($cat->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td contenteditable="true"  data-field="photos"> 
                        <select>
                            <option value="0" <?php selected($prompt['photos'] ?? '', '0'); ?>>0</option>
                            <option value="1" <?php selected($prompt['photos'] ?? '', '1'); ?>>1</option>
                            <option value="2" <?php selected($prompt['photos'] ?? '', '2'); ?>>2</option>
                            <option value="3" <?php selected($prompt['photos'] ?? '', '3'); ?>>3</option>
                        </select>
                    </td>
                    
                    <td>
                        <input type="checkbox" class="toggle-active" data-field="active" <?php checked($prompt['active']?? 0, "1"); ?> />
                    </td>
                    <td>
                        <button class="delete-prompt button button-danger">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <button id="add-prompt" class="button button-primary">Add New Prompt</button>
    <hr>
    <form method="POST" class="ai-storymaker-settings" id="prompt-form">
        <?php wp_nonce_field('save_story_prompts', 'story_prompts_nonce'); ?>
        <input type="hidden" name="prompts" id="prompts-data" value="">

        <input type="submit" name="save_prompts_v2" value="Save Changes" class="button button-primary">
    </form>

</div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const promptList = document.getElementById("prompt-list");
    const prompt_form = document.getElementById("prompt-form");
    const promptsData = document.getElementById("prompts-data");
    
    // Capture inline edits
    document.querySelectorAll(".editable").forEach(element => {
        element.addEventListener("input", function() {
            element.dataset.changed = "true";
        });
    });

    // Toggle active checkbox
    document.querySelectorAll(".toggle-active").forEach(checkbox => {
        checkbox.addEventListener("change", function() {
            checkbox.dataset.changed = "true";
        });
    });

    // Capture delete action
    document.querySelectorAll(".delete-prompt").forEach(button => {
        button.addEventListener("click", function() {
            button.closest("tr").remove();
        });
    });

    // Add new prompt
    document.getElementById("add-prompt").addEventListener("click", function() {
        const newRow = document.createElement("tr");
   
        newRow.innerHTML = `
            <td contenteditable="true" class="editable" data-field="text">New Prompt</td>
            <td  data-field="category">
                <select >
                    <?php 
                    $categories = get_categories(array('hide_empty' => false));
                    foreach ($categories as $cat) : ?>
                        <option value="<?php echo esc_attr($cat->slug); ?>">
                            <?php echo esc_html($cat->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td contenteditable="true" class="editable" data-field="photos"> 
                <select >
                    <option value="0">0</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                </select>
            </td>
            <td><input type="checkbox" class="toggle-active" data-field="active" /></td>
            <td><button class="delete-prompt button button-danger" OnClick="this.closest('tr').remove();">Delete</button></td>
 
        `;
        promptList.appendChild(newRow);
    });

    // Handle form submission
    prompt_form.addEventListener("submit", function(event) {

        let settings = {
            "default_settings": {
            "model": document.getElementById("model").value,
            "system_content": document.getElementById("system_content").value
            },
            "prompts": []
        };

        document.querySelectorAll("#prompt-list tr").forEach(row => {
            const textEl = row.querySelector("[data-field='text']");
            if (textEl && textEl.innerText.trim() !== "") {
            settings.prompts.push({
                text: textEl.innerText.trim(),
                category: row.querySelector("[data-field='category'] select").value,
                photos: row.querySelector("[data-field='photos'] select").value,
                active: row.querySelector("[data-field='active']").checked ? 1 : 0
            });
            }
        });
        
        promptsData.value = JSON.stringify(settings).replace(/\\"/g, '"');


        prompt_form.submit();

    });
});
</script>
