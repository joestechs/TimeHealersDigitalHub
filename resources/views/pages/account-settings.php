<section class="page-hero"><div class="container"><span class="eyebrow">Account settings</span><h1>Your profile & security.</h1><p>Keep your contact details current and protect your account.</p></div></section>
<section class="content-section"><div class="container account-settings-grid">
  <form class="form-card" method="post" action="<?= $this->escape(appUrl('/account/profile')) ?>">
    <span class="eyebrow">Profile details</span><h2>Contact information</h2>
    <input type="hidden" name="csrf" value="<?= $this->escape($_SESSION['csrf'] ?? '') ?>">
    <div class="field"><label for="profile-name">Full name</label><input id="profile-name" name="name" value="<?= $this->escape($user['name']) ?>" autocomplete="name" required></div>
    <div class="field"><label for="profile-email">Email address</label><input id="profile-email" name="email" type="email" value="<?= $this->escape($user['email']) ?>" autocomplete="email" required></div>
    <button class="button" type="submit">Save profile</button>
  </form>
  <form class="form-card" method="post" action="<?= $this->escape(appUrl('/account/password')) ?>">
    <span class="eyebrow">Account security</span><h2>Change password</h2>
    <input type="hidden" name="csrf" value="<?= $this->escape($_SESSION['csrf'] ?? '') ?>">
    <div class="field"><label for="current-password">Current password</label><input id="current-password" name="current_password" type="password" autocomplete="current-password" required></div>
    <div class="field"><label for="new-password">New password (12+ characters)</label><input id="new-password" name="new_password" type="password" minlength="12" autocomplete="new-password" required></div>
    <div class="field"><label for="password-confirmation">Confirm new password</label><input id="password-confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required></div>
    <button class="button" type="submit">Update password</button>
  </form>
</div></section>
