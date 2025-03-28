<?php defined('ABSPATH') || exit; ?>
<div class="wrap">
    <div class="plugin-header">
        <img src="<?php echo esc_url(plugins_url('assets/img/icon-svg.svg', dirname(dirname(dirname(__FILE__))))); ?>" alt="<?php esc_attr_e('Free Mail SMTP', 'free-mail-smtp'); ?>" class="plugin-logo">
        <h1><?php esc_html_e('FREE MAIL', 'free-mail-smtp'); ?> <span><?php esc_html_e('SMTP', 'free-mail-smtp'); ?></span></h1>
    </div>

    <p class="description"><?php esc_html_e('Setup custom SMTP or popular Providers to improve your WordPress email deliverability.', 'free-mail-smtp'); ?></p>

    <nav class="free-mail-smtp-nav-tab-wrapper">
        <a href="<?php echo esc_url(admin_url('admin.php?page=free_mail_smtp-providers')); ?>" class="free-mail-smtp-nav-tab"><?php esc_html_e('Providers', 'free-mail-smtp'); ?></a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=free_mail_smtp-logs')); ?>" class="free-mail-smtp-nav-tab"><?php esc_html_e('Email Logs', 'free-mail-smtp'); ?></a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=free_mail_smtp-analytics')); ?>" class="free-mail-smtp-nav-tab"><?php esc_html_e('Providers Logs', 'free-mail-smtp'); ?></a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=free_mail_smtp-email-router')); ?>" class="free-mail-smtp-nav-tab free-mail-smtp-nav-tab-active"><?php esc_html_e('Email Router', 'free-mail-smtp'); ?></a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=free_mail_smtp-settings')); ?>" class="free-mail-smtp-nav-tab"><?php esc_html_e('Settings', 'free-mail-smtp'); ?></a>
    </nav>

    <?php settings_errors('free_mail_smtp_messages'); ?>

    <div class="tabset-content">
        <div class="table-header">
            <a href="#" class="page-title-action add-router-condition">
                <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Add Router Condition', 'free-mail-smtp'); ?>
            </a>
        </div>

        <div class="providers-table-wrapper">
            <table class="widefat fixed providers-table">
                <thead>
                    <tr>
                        <th class="column-label"><?php esc_html_e('Label', 'free-mail-smtp'); ?></th>
                        <th class="column-provider"><?php esc_html_e('Enabled', 'free-mail-smtp'); ?></th>
                        <th class="column-actions"><?php esc_html_e('Actions', 'free-mail-smtp'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($conditions_list)): ?>
                        <tr class="no-items">
                            <td colspan="5" class="empty-state">
                                <img src="<?php echo esc_url(plugins_url('assets/img/icon-svg.svg', dirname(dirname(dirname(__FILE__))))); ?>" alt="<?php esc_attr_e('No providers', 'free-mail-smtp'); ?>" class="empty-state-icon">
                                <p><?php esc_html_e('It seems you haven\'t added any routing condition yet. Get started now.', 'free-mail-smtp'); ?></p>
                                <button type="button" class="button button-primary save-condition" id="add-router-condition-button">
                                    <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Add Router Condition', 'free-mail-smtp'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($conditions_list as $condition): ?>
                            <tr>
                                <td class="column-label">
                                    <strong><?php echo esc_html($condition->condition_label); ?></strong>
                                </td>
                                <td class="column-provider">
                                    <div class="toggle-container">
                                        <label class="toggle-switch">
                                            <input type="checkbox" class="toggle-is-enabled" data-id="<?php echo esc_attr($condition->id); ?>" <?php checked($condition->is_enabled, 1); ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </td>
                                <td class="column-actions">
                                    <button type="button" class="button button-primary edit-condition" data-id="<?php echo esc_attr($condition->id); ?>">
                                        <span class="dashicons dashicons-edit"></span> <?php esc_html_e('Edit', 'free-mail-smtp'); ?>
                                    </button>
                                    <button type="button" class="button button-secondary delete-condition" data-id="<?php echo esc_attr($condition->id); ?>">
                                        <span class="dashicons dashicons-trash"></span> <?php esc_html_e('Delete', 'free-mail-smtp'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div id="router-modal" class="modal" style="display:none;">
            <div class="conditions-modal-content">
                <div class="conditions-modal-header">
                    <h2><?php esc_html_e('Configure Router Condition', 'free-mail-smtp'); ?></h2>
                    <button type="button" onclick="FreeMailSMTPRouter.closeModal(false)" class="conditions-modal-close">&times;</button>
                </div>
                <div class="conditions-modal-body">
                    <?php
                    $modal = dirname(__FILE__) . '/partials/modal.php';
                    if (file_exists($modal)) {
                        include $modal;
                    }
                    ?>
            </div>
            <div class="conditions-modal-footer">
                <div class="conditions-modal-footer-buttons">
                    <button type="button" class="btn btn-secondary" onclick="FreeMailSMTPRouter.closeModal(false)"><?php esc_html_e('Close', 'free-mail-smtp'); ?></button>
                    <button type="button" class="btn btn-primary save-condition" onclick="FreeMailSMTPRouter.saveRouter()"><?php esc_html_e('Save', 'free-mail-smtp'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>