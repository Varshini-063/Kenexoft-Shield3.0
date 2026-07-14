<div
    id="auth-app"
    class="app-shell auth-shell"
    data-mode="login"
    data-csrf-url="<?= $escape($asset('api/csrf-token')) ?>"
    data-login-url="<?= $escape($asset('api/auth/login')) ?>"
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
            <h2>Secure Console Access</h2>
            <p>Authenticate your SHIELD identity, resume node operations, and continue protected onboarding workflows.</p>
        </section>

        <section class="vector-panel">
            <h3>Authentication Vector</h3>
            <div class="metric-empty">JWT-backed access with CSRF protection and audit logging.</div>
            <ul class="assurance-list">
                <li>Secure HttpOnly session cookie</li>
                <li>Persona-aware dashboard routing</li>
                <li>Prepared-statement credential checks</li>
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
                <form id="login-form" class="form-stack auth-form" novalidate>
                    <header class="section-head">
                        <div class="overline">Access Gateway</div>
                        <h2>Login to Kenexoft SHIELD</h2>
                        <p>Use the email and password created during registration.</p>
                    </header>

                    <div class="field" data-error-wrap="email">
                        <label for="login-email">
                            <span>Email Address</span>
                            <span class="required-mark">* Required</span>
                        </label>
                        <input id="login-email" name="email" type="email" autocomplete="email" placeholder="security@example.com" required>
                        <p class="error-text" data-error-for="email"></p>
                    </div>

                    <div class="field" data-error-wrap="password">
                        <label for="login-password">
                            <span>Password</span>
                            <span class="required-mark">* Required</span>
                        </label>
                        <div class="password-wrap">
                            <input id="login-password" name="password" type="password" autocomplete="current-password" placeholder="Password" required>
                            <button class="icon-button" type="button" data-auth-action="toggle-password" data-target="login-password" aria-label="Show password">Show</button>
                        </div>
                        <p class="error-text" data-error-for="password"></p>
                    </div>

                    <div class="field" data-error-wrap="role">
                        <label for="login-role">
                            <span>Login As</span>
                            <span class="required-mark">* Required</span>
                        </label>
                        <select id="login-role" name="role" required>
                            <option value="SUBSCRIBER">Subscriber</option>
                            <option value="SUPER_ADMIN">Super Admin</option>
                        </select>
                        <p class="error-text" data-error-for="role"></p>
                    </div>

                    <div class="auth-options">
                        <label class="auth-check">
                            <input id="remember-me" name="remember" type="checkbox">
                            <span>Remember Me</span>
                        </label>
                        <a href="<?= $escape($asset('forgot-password')) ?>">Forgot Password?</a>
                    </div>

                    <p class="submit-error" data-auth-message hidden></p>

                    <div class="auth-actions">
                        <button type="submit" class="btn btn-green" data-submit-label="Login">Login</button>
                        <a class="btn btn-muted" href="<?= $escape($asset('registration')) ?>">Back to Registration</a>
                    </div>
                </form>
            </div>
        </section>
    </main>
</div>
