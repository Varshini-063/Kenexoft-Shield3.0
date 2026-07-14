<div
    id="auth-app"
    class="app-shell auth-shell"
    data-mode="reset"
    data-csrf-url="<?= $escape($asset('api/csrf-token')) ?>"
    data-reset-url="<?= $escape($asset('api/auth/reset-password')) ?>"
>
    <aside class="side-panel">
        <div class="side-grid" aria-hidden="true"></div>
        <div class="brand-block">
            <div class="brand-lock" aria-hidden="true">SH</div>
            <div>
                <h1>KENEXOFT</h1>
                <span>SHIELD v3.0</span>
            </div>
        </div>

        <section class="brand-copy">
            <h2>Reset Secure Access</h2>
            <p>Create a fresh password using the one-time reset token issued for your SHIELD account.</p>
        </section>

        <section class="vector-panel">
            <h3>Password Policy</h3>
            <div class="metric-empty">Passwords are stored using PHP bcrypt hashing.</div>
            <ul class="assurance-list">
                <li>Minimum 8 characters</li>
                <li>Uppercase, lowercase, number, and symbol</li>
                <li>Token invalidated after success</li>
            </ul>
        </section>

        <footer class="side-footer">
            <p>&copy; 2026 Kenexoft Technologies. All rights reserved.</p>
            <p>SHIELD and all related assets are registered trademarks.</p>
        </footer>
    </aside>

    <main class="main-panel auth-main">
        <section class="workspace">
            <div class="workspace-card auth-card">
                <form id="reset-form" class="form-stack auth-form" novalidate>
                    <header class="section-head">
                        <div class="overline">Credential Reset</div>
                        <h2>Set a New Password</h2>
                        <p>Complete the reset with a strong password that matches the registration policy.</p>
                    </header>

                    <input type="hidden" name="token" value="<?= $escape($token ?? '') ?>">

                    <div class="field" data-error-wrap="token">
                        <label for="reset-token">
                            <span>Reset Token</span>
                            <span class="required-mark">* Required</span>
                        </label>
                        <input id="reset-token" name="tokenDisplay" type="text" value="<?= $escape($token ?? '') ?>" placeholder="Paste reset token" autocomplete="off">
                        <p class="error-text" data-error-for="token"></p>
                    </div>

                    <div class="field" data-error-wrap="password">
                        <label for="reset-password">
                            <span>New Password</span>
                            <span class="required-mark">* Required</span>
                        </label>
                        <div class="password-wrap">
                            <input id="reset-password" name="password" type="password" autocomplete="new-password" placeholder="New password" required>
                            <button class="icon-button" type="button" data-auth-action="toggle-password" data-target="reset-password" aria-label="Show password">Show</button>
                        </div>
                        <p class="error-text" data-error-for="password"></p>
                    </div>

                    <div class="field" data-error-wrap="confirmPassword">
                        <label for="reset-confirm-password">
                            <span>Confirm Password</span>
                            <span class="required-mark">* Required</span>
                        </label>
                        <div class="password-wrap">
                            <input id="reset-confirm-password" name="confirmPassword" type="password" autocomplete="new-password" placeholder="Confirm password" required>
                            <button class="icon-button" type="button" data-auth-action="toggle-password" data-target="reset-confirm-password" aria-label="Show password">Show</button>
                        </div>
                        <p class="error-text" data-error-for="confirmPassword"></p>
                    </div>

                    <p class="submit-error" data-auth-message hidden></p>

                    <div class="auth-actions">
                        <button type="submit" class="btn btn-green" data-submit-label="Reset Password">Reset Password</button>
                        <a class="btn btn-muted" href="<?= $escape($asset('login')) ?>">Back to Login</a>
                    </div>
                </form>
            </div>
        </section>
    </main>
</div>
