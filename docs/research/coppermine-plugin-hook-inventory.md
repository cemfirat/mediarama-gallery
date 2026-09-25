# Coppermine plugin hook inventory

Status: **verified first-pass hook inventory**
Date: 2026-09-25
Tracking: #11

Primary source: direct searches for `CPGPluginAPI::action` and `CPGPluginAPI::filter` in the 1.6 and 1.7 repositories.

This inventory is intentionally source-derived. It is not yet a guarantee that every dynamically constructed or indirect hook has been captured.

## 1.6 action hooks observed

- `add_file_data_success`
- `after_delete_file`
- `after_edit_file`
- `authorize_user`
- `before_delete_file`
- `captcha_comment_validate`
- `captcha_contact_validate`
- `captcha_ecard_validate`
- `comment_approve`
- `comment_update`
- `page_start`
- `plugin_cleanup`
- `plugin_configure`
- `plugin_wakeup`
- `post_breadcrumb`
- `profile_display_form`
- `profile_submit_form`
- `register_form_submit`
- `register_user_activation`
- `theme_thumbnails_wrapper_end`
- `theme_thumbnails_wrapper_start`
- `upload_form`
- `upload_process`

## 1.6 filter hooks observed

- `add_file_data`
- `captcha_contact_print`
- `captcha_ecard_print`
- `captcha_register_print`
- `cpg_mail_sender_email`
- `cpg_mail_to_email`
- `file_info`
- `html_image_reduced_overlay`
- `image_sharpen`
- `ip_information`
- `javascript_includes`
- `main_page_layout`
- `page_html`
- `page_meta`
- `profile_add_data`
- `register_form_create`
- `search_form`
- `smilies_display`
- `smilies_process`
- `sub_menu`
- `sys_menu`
- `theme_name`
- `theme_pageheader_params`
- `thumb_caption_lastupby`
- `upload_file_name`
- `upload_options`
- `user_caption_params`
- `usermgr_footer`
- `usermgr_header`

## Additional 1.7 filter hooks observed in the first pass

The 1.7 source search exposes additional filter names not observed in the equivalent 1.6 search results:

- `replace_forbidden_conditions`
- `theme_thumbnails_footer`
- `token_criteria`

The shared action-hook set found in the first pass is otherwise highly similar.

## Hook coverage by product area

The hook names confirm extension points around:

- media/file creation and deletion;
- upload forms/options/processing;
- image processing/sharpening;
- media/file info;
- registration and activation;
- profile forms/data;
- authorization;
- comments/moderation;
- captcha;
- e-cards/contact;
- mail routing/sender;
- search form;
- menus/layout/page HTML/meta;
- JavaScript inclusion;
- themes and thumbnail wrappers;
- user manager UI;
- plugin lifecycle.

This breadth is an important Coppermine strength: plugins can affect more than presentation.

## Mediarama implication

The goal is **not** source compatibility with these hook names.

Mediarama should preserve the underlying extensibility outcomes through:

- typed domain/application events;
- explicit extension contracts;
- UI slots/components;
- authorization-policy extension points;
- media-inspection/processing extension points;
- controlled metadata normalization hooks;
- lifecycle hooks with documented ordering and failure semantics.

Hooks that can arbitrarily rewrite raw HTML/global state should not be reproduced by default.

## Remaining hook audit

Before plugin architecture can be considered fully researched:

- inspect `include/plugin_api.inc.php` lifecycle and priority semantics;
- inspect plugin install/config/uninstall persistence;
- inspect sample plugin(s) end to end;
- identify whether hooks can stop/default behavior or only transform data;
- map parameter/return contracts for every high-value hook;
- identify dynamically constructed hook names not caught by source search;
- compare 1.6 vs 1.7 plugin-manager behavior;
- classify which Coppermine plugin capabilities deserve first-class Mediarama extension APIs.
