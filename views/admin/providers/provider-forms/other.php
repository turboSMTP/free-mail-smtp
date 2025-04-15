<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="wizard-step">
    
    <h3><?php echo isset($is_edit) && $is_edit ? 'Edit SMTP Configuration' : 'Add SMTP Provider'; ?></h3>
    <p class="description">Enter your SMTP credentials below.</p>

    <form id="provider-form" method="post">
        <?php wp_nonce_field('free_mail_smtp_nonce', 'free_mail_smtp_nonce'); ?>
        
        <input type="hidden" name="provider" id="provider" value="other">
        <input type="hidden" name="connection_id" id="connection_id" value="">
        
        <table class="form-table">
        <tr>
            <th scope="row">
                    <label for="connection_label">Connection Label</label>
                </th>
                <td>
                    <input type="text" 
                           name="connection_label" 
                           id="connection_label" 
                           class="regular-text" 
                           required>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="email_from_overwrite">Email From</label>
                </th>
                <td>
                    <input type="email"
                        name="config_keys[email_from_overwrite]"
                        id="email_from_overwrite"
                        class="regular-text"
                        >
                        <p class="description">(Optional) Force sender email for this provider</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="smtp_host">SMTP Host</label>
                </th>
                <td>
                    <input type="text" 
                           name="config_keys[smtp_host]" 
                           id="smtp_host" 
                           class="regular-text" 
                           required>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="smtp_user">SMTP Username</label>
                </th>
                <td>
                    <input type="text" 
                           name="config_keys[smtp_user]" 
                           id="smtp_user" 
                           class="regular-text" 
                           required>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="smtp_pw">SMTP Password</label>
                </th>
                <td>
                    <div class="smtp-pw-wrapper">
                    <input type="password" 
                           name="config_keys[smtp_pw]" 
                           id="smtp_pw" 
                           class="regular-text" 
                           required>
                           <span id="toggle_smtp_pw" class="dashicons dashicons-visibility"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="smtp_encryption">Encryption</label>
                </th>
                <td>
                    <select name="config_keys[smtp_encryption]" id="smtp_encryption" required>
                        <option value="none">None</option>
                        <option value="ssl">SSL</option>
                        <option value="tls">TLS</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="smtp_port">Port</label>
                </th>
                <td>
                    <input type="number" 
                           name="config_keys[smtp_port]" 
                           id="smtp_port" 
                           class="small-text" 
                           min="1" 
                           value=""
                           required>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="priority">Priority</label>
                </th>
                <td>
                    <input type="number" 
                           name="priority" 
                           id="priority" 
                           class="small-text" 
                           min="1" 
                           value="1"
                           required>
                </td>
            </tr>
        </table>

        <div class="submit-wrapper">
            <?php if (!(isset($is_edit) && $is_edit)): ?>
                <button type="button" class="button back-step">Back</button>
            <?php endif; ?>
            <button type="submit" class="button button-primary save-provider">
                <?php echo isset($is_edit)  ? 'Update SMTP' : 'Add SMTP'; ?>
            </button>
        </div>
    </form>
</div>