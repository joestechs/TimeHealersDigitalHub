<?php
$settings = $sectionData['settings'];
$edit = $sectionData['edit'] ?? null;
if (($edit['status'] ?? '') === 'archived') $edit = null;
$preview = $sectionData['importPreview'] ?? null;
$validPreviewRows = $preview ? count(array_filter($preview['rows'], static fn (array $row): bool => $row['errors'] === [])) : 0;
?>
<section class="admin-content chatbot-admin">
  <div class="admin-section-intro">
    <div><span class="eyebrow">Customer experience</span><h2>Helpful, verified answers</h2><p>Manage the public assistant and its curated knowledge. Customer messages are not saved as chat transcripts.</p></div>
  </div>

  <form class="admin-form-card" method="post" action="<?= $this->escape(appUrl('/admin/chatbot/settings')) ?>">
    <input type="hidden" name="csrf" value="<?= $this->escape($_SESSION['csrf'] ?? '') ?>">
    <div class="admin-panel-heading"><div><span class="eyebrow">Chat experience</span><h2>Assistant settings</h2></div><label class="admin-checkbox"><input type="checkbox" name="is_enabled" value="1" <?= !empty($settings['is_enabled']) ? 'checked' : '' ?>> Enable public chat</label></div>
    <div class="form-grid">
      <div class="field"><label for="chatbot-name">Assistant name *</label><input id="chatbot-name" name="bot_name" maxlength="80" value="<?= $this->escape($settings['bot_name']) ?>" required></div>
      <div class="field"><label for="chatbot-support-path">Fallback page path *</label><input id="chatbot-support-path" name="support_path" maxlength="255" value="<?= $this->escape($settings['support_path']) ?>" required><small>Use a local page such as /contact.</small></div>
    </div>
    <div class="field"><label for="chatbot-greeting">Welcome message *</label><textarea id="chatbot-greeting" name="greeting" rows="2" maxlength="500" required><?= $this->escape($settings['greeting']) ?></textarea></div>
    <div class="field"><label for="chatbot-fallback">No-match response *</label><textarea id="chatbot-fallback" name="fallback_message" rows="2" maxlength="500" required><?= $this->escape($settings['fallback_message']) ?></textarea></div>
    <div class="admin-form-footer"><button class="button" type="submit">Save assistant settings</button></div>
    <p class="chatbot-admin-note">Optional AI-provider configuration is server-side only. Set CHATBOT_API_URL, CHATBOT_API_KEY, and CHATBOT_MODEL in the application environment; when enabled, the chat widget requires customer consent before sending messages to that provider.</p>
  </form>

  <section class="admin-panel chatbot-test-panel">
    <div class="admin-panel-heading"><div><span class="eyebrow">No-write test</span><h2>Try a support question</h2></div></div>
    <p>Tests use the same published knowledge and public catalog retrieval as the live assistant. The test question and answer are not added to the knowledge base or saved as a conversation.</p>
    <form method="post" action="<?= $this->escape(appUrl('/admin/chatbot/test')) ?>">
      <input type="hidden" name="csrf" value="<?= $this->escape($_SESSION['csrf'] ?? '') ?>">
      <div class="field"><label for="chatbot-test-message">Test question</label><textarea id="chatbot-test-message" name="test_message" rows="2" maxlength="1000" required></textarea></div>
      <?php if (!empty($sectionData['externalProcessing'])): ?><label class="chatbot-admin-consent"><input type="checkbox" name="consent" value="1" required> I understand this test question may be sent to the configured AI provider.</label><?php endif ?>
      <button class="button button-ghost" type="submit">Run test</button>
    </form>
    <?php if (!empty($sectionData['testResult'])): ?>
      <div class="chatbot-test-result" role="status"><span class="eyebrow">Response · <?= $this->escape($sectionData['testResult']['mode']) ?></span><p><?= $this->escape($sectionData['testResult']['answer']) ?></p><?php if (!empty($sectionData['testResult']['action']['url'])): ?><a href="<?= $this->escape($sectionData['testResult']['action']['url']) ?>"><?= $this->escape($sectionData['testResult']['action']['label']) ?></a><?php endif ?></div>
    <?php endif ?>
  </section>

  <div class="chatbot-admin-grid">
    <form class="admin-form-card" method="post" action="<?= $this->escape(appUrl('/admin/chatbot/knowledge')) ?>">
      <input type="hidden" name="csrf" value="<?= $this->escape($_SESSION['csrf'] ?? '') ?>">
      <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
      <div class="admin-panel-heading"><div><span class="eyebrow">Curated knowledge</span><h2><?= $edit ? 'Edit answer' : 'Add an answer' ?></h2></div></div>
      <div class="field"><label for="chatbot-question">Question or topic *</label><input id="chatbot-question" name="question" maxlength="255" value="<?= $this->escape($edit['question'] ?? '') ?>" required></div>
      <div class="field"><label for="chatbot-answer">Verified answer *</label><textarea id="chatbot-answer" name="answer" rows="6" maxlength="12000" required><?= $this->escape($edit['answer'] ?? '') ?></textarea><small>Use public, verified project information only. Do not add account, order, payment, or secret data.</small></div>
      <div class="form-grid">
        <div class="field"><label for="chatbot-category">Category *</label><input id="chatbot-category" name="category" maxlength="80" value="<?= $this->escape($edit['category'] ?? 'general') ?>" required></div>
        <div class="field"><label for="chatbot-keywords">Search keywords</label><input id="chatbot-keywords" name="keywords" maxlength="1000" value="<?= $this->escape($edit['keywords'] ?? '') ?>" placeholder="Separate terms with |"></div>
      </div>
      <div class="form-grid">
        <div class="field"><label for="chatbot-link-label">Related-link label</label><input id="chatbot-link-label" name="link_label" maxlength="100" value="<?= $this->escape($edit['link_label'] ?? '') ?>"></div>
        <div class="field"><label for="chatbot-link-path">Related local page path</label><input id="chatbot-link-path" name="link_path" maxlength="255" value="<?= $this->escape($edit['link_path'] ?? '') ?>" placeholder="/contact"></div>
      </div>
      <div class="form-grid">
        <div class="field"><label for="chatbot-status">Publication</label><select id="chatbot-status" name="status"><?php foreach (['draft' => 'Draft', 'published' => 'Published'] as $value => $label): ?><option value="<?= $this->escape($value) ?>" <?= ($edit['status'] ?? 'draft') === $value ? 'selected' : '' ?>><?= $this->escape($label) ?></option><?php endforeach ?></select></div>
        <div class="field"><label for="chatbot-order">Sort order</label><input id="chatbot-order" type="number" name="sort_order" min="-1000" max="1000" value="<?= (int) ($edit['sort_order'] ?? 0) ?>"></div>
      </div>
      <div class="admin-form-footer"><button class="button" type="submit"><?= $edit ? 'Save answer' : 'Add answer' ?></button><?php if ($edit): ?><a class="button button-ghost" href="<?= $this->escape(appUrl('/admin/chatbot')) ?>">Cancel</a><?php endif ?></div>
    </form>

    <section class="admin-form-card chatbot-import-card">
      <div class="admin-panel-heading"><div><span class="eyebrow">Bulk knowledge</span><h2>Import CSV or JSON</h2></div></div>
      <p>Upload up to 100 records (256 KB maximum). The preview validates every row and makes no database changes until you confirm.</p>
      <p class="chatbot-template-links"><a href="<?= $this->escape(appUrl('/assets/templates/chatbot-knowledge-template.csv')) ?>" download>CSV template</a><a href="<?= $this->escape(appUrl('/assets/templates/chatbot-knowledge-template.json')) ?>" download>JSON template</a></p>
      <form method="post" enctype="multipart/form-data" action="<?= $this->escape(appUrl('/admin/chatbot/import-preview')) ?>">
        <input type="hidden" name="csrf" value="<?= $this->escape($_SESSION['csrf'] ?? '') ?>">
        <input type="hidden" name="MAX_FILE_SIZE" value="<?= \App\Services\ChatbotImportParser::MAX_BYTES ?>">
        <div class="field"><label for="chatbot-import-file">Knowledge file</label><input id="chatbot-import-file" type="file" name="knowledge_file" accept=".csv,.json,application/json,text/csv" required></div>
        <button class="button button-ghost" type="submit">Preview import</button>
      </form>
      <p class="chatbot-admin-note">Imported records default to drafts unless the file explicitly marks them published. Duplicate questions are skipped unless you choose to update them during confirmation.</p>
    </section>
  </div>

  <?php if ($preview): ?>
    <section class="admin-panel chatbot-preview">
      <div class="admin-panel-heading"><div><span class="eyebrow">Review before commit</span><h2>Import preview: <?= $this->escape($preview['name']) ?></h2></div><span class="admin-count"><?= $validPreviewRows ?> valid / <?= count($preview['rows']) ?> total</span></div>
      <div class="table-wrap"><table class="admin-table"><thead><tr><th>Question</th><th>Status</th><th>Validation</th></tr></thead><tbody>
        <?php foreach ($preview['rows'] as $row): ?>
          <tr>
            <td><strong><?= $this->escape($row['data']['question']) ?></strong><small class="table-subtext"><?= $this->escape($row['data']['category']) ?></small><?php if (!empty($row['existing_question'])): ?><small class="table-subtext">Matches existing “<?= $this->escape($row['existing_question']) ?>” (<?= $this->escape($row['existing_status']) ?>)</small><?php endif ?></td>
            <td><?= $this->escape(ucfirst($row['data']['status'])) ?></td>
            <td>
              <?php if ($row['errors']): ?><span class="status-badge status-archived">Row <?= (int) $row['row_number'] ?>: <?= $this->escape(implode(' ', $row['errors'])) ?></span>
              <?php elseif (!empty($row['duplicate_id']) || !empty($row['duplicate_in_file'])): ?><span class="status-badge status-draft"><?= !empty($row['duplicate_id']) ? 'Existing question' : 'Repeated in this file' ?></span>
              <?php else: ?><span class="status-badge status-published">Ready</span><?php endif ?>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody></table></div>
      <form class="chatbot-import-confirm" method="post" action="<?= $this->escape(appUrl('/admin/chatbot/import-commit')) ?>">
        <input type="hidden" name="csrf" value="<?= $this->escape($_SESSION['csrf'] ?? '') ?>">
        <input type="hidden" name="preview_token" value="<?= $this->escape($preview['token']) ?>">
        <label for="chatbot-duplicate-mode">When a stable key already exists</label>
        <select id="chatbot-duplicate-mode" name="duplicate_mode"><option value="skip">Skip duplicate records</option><option value="update">Update existing records from this file</option></select>
        <button class="button" type="submit" <?= $validPreviewRows === 0 ? 'disabled' : '' ?>>Confirm import</button>
        <a class="button button-ghost" href="<?= $this->escape(appUrl('/admin/chatbot')) ?>">Discard preview</a>
      </form>
    </section>
  <?php endif ?>

  <section class="admin-panel admin-record-panel">
    <div class="admin-panel-heading"><div><span class="eyebrow">Published answers and drafts</span><h2>Knowledge base <span class="admin-count"><?= count($sectionData['records']) ?></span></h2></div>
      <form class="admin-search" method="get" action="<?= $this->escape(appUrl('/admin/chatbot')) ?>"><input type="search" name="q" value="<?= $this->escape($sectionData['query']) ?>" placeholder="Search knowledge"><button class="button button-ghost" type="submit">Search</button></form>
    </div>
    <?php if (!$sectionData['records']): ?><p class="admin-empty">No knowledge records match this search.</p><?php else: ?>
      <div class="table-wrap"><table class="admin-table"><thead><tr><th>Question</th><th>Category</th><th>Source</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($sectionData['records'] as $record): ?>
          <tr>
            <td><strong><?= $this->escape($record['question']) ?></strong></td><td><?= $this->escape($record['category']) ?></td><td><?= $this->escape(ucfirst($record['source_type'])) ?></td>
            <td><span class="status-badge status-<?= $this->escape($record['status']) ?>"><?= $this->escape(ucfirst($record['status'])) ?></span></td>
            <td>
              <?php if ($record['status'] === 'archived'): ?>
                <form class="chatbot-inline-action" method="post" action="<?= $this->escape(appUrl('/admin/chatbot/restore')) ?>"><input type="hidden" name="csrf" value="<?= $this->escape($_SESSION['csrf'] ?? '') ?>"><input type="hidden" name="id" value="<?= (int) $record['id'] ?>"><button class="admin-icon-link" type="submit" aria-label="Restore answer as draft"><i data-lucide="rotate-ccw"></i></button></form>
              <?php else: ?>
                <a class="admin-icon-link" aria-label="Edit answer" href="<?= $this->escape(appUrl('/admin/chatbot?id=' . (int) $record['id'])) ?>"><i data-lucide="pencil"></i></a>
                <form class="chatbot-inline-action" method="post" action="<?= $this->escape(appUrl('/admin/chatbot/archive')) ?>" onsubmit="return confirm('Archive this chatbot answer?')"><input type="hidden" name="csrf" value="<?= $this->escape($_SESSION['csrf'] ?? '') ?>"><input type="hidden" name="id" value="<?= (int) $record['id'] ?>"><button class="admin-icon-link" type="submit" aria-label="Archive answer"><i data-lucide="archive"></i></button></form>
              <?php endif ?>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody></table></div>
    <?php endif ?>
  </section>
</section>
