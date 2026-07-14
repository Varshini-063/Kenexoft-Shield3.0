<div
    id="auth-app"
    class="app-shell auth-shell"
    data-mode="forgot"
    data-csrf-url="<?= $escape($asset('api/csrf-token')) ?>"
    data-forgot-url="<?= $escape($asset('api/auth/forgot-password')) ?>"
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
            <h2>Password Recovery</h2>
            <p>Generate a secure reset token for an active SHIELD account without exposing account existence.</p>
        </section>

        <section class="vector-panel">
            <h3>Reset Assurance</h3>
            <div class="metric-empty">Reset tokens expire automatically and can be used only once.</div>
            <ul class="assurance-list">
                <li>Secure random token generation</li>
                <li>Audit logged reset requests</li>
                <li>One-hour reset window</li>
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
                <form id="forgot-form" class="form-stack auth-form" novalidate>
                    <header class="section-head">
                        <div class="overline">Credential Recovery</div>
                        <h2>Forgot Password</h2>
                        <p>Enter your account email. Email delivery is currently represented by a secure reset-link placeholder.</p>
                    </header>

                    <div class="field" data-error-wrap="email">
                        <label for="forgot-email">
                            <span>Email</span>
                            <span class="required-mark">* Required</span>
                        </label>
                        <input id="forgot-email" name="email" type="email" autocomplete="email" placeholder="security@example.com" required>
                        <p class="error-text" data-error-for="email"></p>
                    </div>

                    <p class="submit-error" data-auth-message hidden></p>
                    <div class="auth-reset-link" data-reset-link hidden></div>

                    <div class="auth-actions">
                        <button type="submit" class="btn btn-green" data-submit-label="Send Reset Link">Send Reset Link</button>
                        <a class="btn btn-muted" href="<?= $escape($asset('login')) ?>">Back to Login</a>
                    </div>
                </form>
            </div>
        </section>
    </main>
</div>
