<div
    id="shield-app"
    class="app-shell"
    data-submit-url="<?= $escape($asset('api/auth/register')) ?>"
    data-csrf-url="<?= $escape($asset('api/csrf-token')) ?>"
    data-login-url="<?= $escape($asset('login')) ?>"
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
            <h2>Enterprise Cyber defense &amp; Node Management</h2>
            <p>Provision multi-persona registration nodes, calculate live state compliance tariffs, and connect your business vectors to the Kenexoft SHIELD perimeter.</p>
        </section>

        <section class="vector-panel">
            <h3>Active Vector Metrics</h3>
            <div id="active-vector" class="metric-empty">Awaiting persona selection to bind node configurations.</div>
            <ul class="assurance-list">
                <li>WCAG 2.1 compliant onboarding forms</li>
                <li>Automated IGST/CGST tax classification engine</li>
                <li>Dynamic branch location provisioning</li>
            </ul>
        </section>

        <footer class="side-footer">
            <p>&copy; 2026 Kenexoft Technologies. All rights reserved.</p>
            <p>SHIELD and all related assets are registered trademarks.</p>
        </footer>
    </aside>

    <main class="main-panel">
        <div id="stepper" class="stepper-wrap" hidden></div>
        <section class="workspace">
            <div class="workspace-card">
                <form
                    id="registration-form"
                    method="post"
                    action="<?= $escape($asset('api/auth/register')) ?>"
                    enctype="multipart/form-data"
                    novalidate
                >
                    <div id="form-content" class="form-content"></div>
                    <div id="nav-footer" class="nav-footer" hidden></div>
                </form>
            </div>
        </section>
    </main>

    <noscript>
        <div class="noscript-alert">Kenexoft SHIELD registration requires JavaScript for the interactive onboarding wizard.</div>
    </noscript>
</div>
