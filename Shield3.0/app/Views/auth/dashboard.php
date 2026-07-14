<div
    id="auth-app"
    class="app-shell auth-shell"
    data-mode="dashboard"
    data-csrf-url="<?= $escape($asset('api/csrf-token')) ?>"
    data-logout-url="<?= $escape($asset('api/auth/logout')) ?>"
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
            <h2><?= $escape($personaLabel ?? 'Dashboard') ?></h2>
            <p>Protected SHIELD control space for authenticated users. Module widgets can be connected here next.</p>
        </section>

        <section class="vector-panel">
            <h3>Session Status</h3>
            <div class="metric-empty">Authenticated with JWT and active account validation.</div>
            <ul class="assurance-list">
                <li>Persona: <?= $escape($user['persona'] ?? 'N/A') ?></li>
                <li>Role: <?= $escape($user['role'] ?? 'SUBSCRIBER') ?></li>
                <li>Status: <?= $escape($user['status'] ?? 'N/A') ?></li>
                <li>Audit logging enabled</li>
            </ul>
        </section>

        <footer class="side-footer">
            <p>&copy; 2026 Kenexoft Technologies. All rights reserved.</p>
            <p>SHIELD and all related assets are registered trademarks.</p>
        </footer>
    </aside>

    <main class="main-panel auth-main">
        <section class="workspace">
            <div class="workspace-card auth-card dashboard-card">
                <div class="form-stack auth-form">
                    <header class="section-head">
                        <div class="overline">Protected Page</div>
                        <h2><?= $escape($personaLabel ?? 'Dashboard') ?></h2>
                        <p>Welcome back, <?= $escape(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['email'] ?? 'SHIELD User')) ?>.</p>
                    </header>

                    <div class="review-grid dashboard-grid">
                        <article class="card persona-review">
                            <span class="overline">Account</span>
                            <h3><?= $escape($user['email'] ?? '') ?></h3>
                            <p>Role-aware dashboard routing is active for <?= $escape($user['role'] ?? 'SUBSCRIBER') ?>.</p>
                        </article>

                        <article class="card persona-review">
                            <span class="overline">Status</span>
                            <h3><?= $escape($user['status'] ?? 'N/A') ?></h3>
                            <p>Only ACTIVE users can access protected dashboard routes.</p>
                        </article>
                    </div>

                    <p class="submit-error" data-auth-message hidden></p>

                    <div class="auth-actions">
                        <button type="button" class="btn btn-dark" data-auth-action="logout" data-submit-label="Logout">Logout</button>
                        <a class="btn btn-muted" href="<?= $escape($asset('registration')) ?>">Registration</a>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>
