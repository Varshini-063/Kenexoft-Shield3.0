(() => {
    const root = document.getElementById('auth-app');
    if (!root) {
        return;
    }

    const mode = root.dataset.mode || '';
    const csrfUrl = root.dataset.csrfUrl || 'api/csrf-token';
    let csrfToken = '';

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const getForm = () => root.querySelector('form');
    const messageEl = () => root.querySelector('[data-auth-message]');

    const valueOf = (form, name) => String(new FormData(form).get(name) || '').trim();

    const setMessage = (message, success = false) => {
        const element = messageEl();
        if (!element) {
            return;
        }

        element.hidden = !message;
        element.textContent = message;
        element.classList.toggle('is-success', success);
    };

    const clearErrors = () => {
        root.querySelectorAll('[data-error-for]').forEach((element) => {
            element.textContent = '';
        });
        root.querySelectorAll('[data-error-wrap]').forEach((element) => {
            element.classList.remove('has-error');
        });
        setMessage('');
    };

    const showErrors = (errors = {}) => {
        Object.entries(errors).forEach(([field, message]) => {
            const error = root.querySelector(`[data-error-for="${CSS.escape(field)}"]`);
            const wrap = root.querySelector(`[data-error-wrap="${CSS.escape(field)}"]`);
            if (error) {
                error.textContent = message;
            }
            if (wrap) {
                wrap.classList.add('has-error');
            }
        });
    };

    const setLoading = (button, loading) => {
        if (!button) {
            return;
        }

        const label = button.dataset.submitLabel || button.textContent || 'Submit';
        button.disabled = loading;
        button.innerHTML = loading ? `<span class="spinner"></span>${escapeHtml(label)}...` : escapeHtml(label);
    };

    const getCsrfToken = async () => {
        if (csrfToken) {
            return csrfToken;
        }

        const response = await fetch(csrfUrl, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        const result = await response.json();
        csrfToken = result.token || '';

        return csrfToken;
    };

    const postJson = async (url, payload) => {
        const token = await getCsrfToken();
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-Token': token,
            },
            body: JSON.stringify(payload),
        });

        const result = await response.json();
        if (!response.ok || !result.success) {
            const error = new Error(result.message || 'Request failed.');
            error.result = result;
            throw error;
        }

        return result;
    };

    const validateLogin = (form) => {
        const errors = {};
        const email = valueOf(form, 'email');
        const data = new FormData(form);
        const password = String(data.get('password') || '');
        const role = String(data.get('role') || '');

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            errors.email = 'Enter a valid email address.';
        }

        if (!password) {
            errors.password = 'Password is required.';
        }

        if (!['SUBSCRIBER', 'SUPER_ADMIN'].includes(role)) {
            errors.role = 'Select a valid login role.';
        }

        return errors;
    };

    const validateForgot = (form) => {
        const email = valueOf(form, 'email');
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
            ? {}
            : { email: 'Enter a valid email address.' };
    };

    const validateReset = (form) => {
        const errors = {};
        const data = new FormData(form);
        const token = String(data.get('tokenDisplay') || data.get('token') || '').trim();
        const password = String(data.get('password') || '');
        const confirmPassword = String(data.get('confirmPassword') || '');

        if (!token) {
            errors.token = 'Password reset token is required.';
        }

        if (password.length < 8) {
            errors.password = 'Password must be at least 8 characters long';
        } else if (!/[A-Z]/.test(password)) {
            errors.password = 'Password must contain at least one uppercase letter';
        } else if (!/[a-z]/.test(password)) {
            errors.password = 'Password must contain at least one lowercase letter';
        } else if (!/[0-9]/.test(password)) {
            errors.password = 'Password must contain at least one number';
        } else if (!/[^A-Za-z0-9]/.test(password)) {
            errors.password = 'Password must contain at least one special character';
        }

        if (!confirmPassword) {
            errors.confirmPassword = 'Please confirm your password';
        } else if (password !== confirmPassword) {
            errors.confirmPassword = 'Passwords do not match';
        }

        return errors;
    };

    const handleLogin = async (form) => {
        const errors = validateLogin(form);
        if (Object.keys(errors).length > 0) {
            showErrors(errors);
            return;
        }

        const data = new FormData(form);
        const result = await postJson(root.dataset.loginUrl || 'api/auth/login', {
            email: valueOf(form, 'email'),
            password: String(data.get('password') || ''),
            role: String(data.get('role') || ''),
            remember: data.get('remember') === 'on',
        });

        setMessage('Login successful. Redirecting to dashboard...', true);
        window.location.href = result.redirect || result.redirectUrl || 'dashboard';
    };

    const handleForgot = async (form) => {
        const errors = validateForgot(form);
        if (Object.keys(errors).length > 0) {
            showErrors(errors);
            return;
        }

        const result = await postJson(root.dataset.forgotUrl || 'api/auth/forgot-password', {
            email: valueOf(form, 'email'),
        });
        setMessage(result.message || 'Password reset instructions are ready.', true);

        const linkBox = root.querySelector('[data-reset-link]');
        if (linkBox && result.resetUrl) {
            linkBox.hidden = false;
            linkBox.innerHTML = `Email placeholder reset link: <a href="${escapeHtml(result.resetUrl)}">${escapeHtml(result.resetUrl)}</a>`;
        }
    };

    const handleReset = async (form) => {
        const errors = validateReset(form);
        if (Object.keys(errors).length > 0) {
            showErrors(errors);
            return;
        }

        const data = new FormData(form);
        const result = await postJson(root.dataset.resetUrl || 'api/auth/reset-password', {
            token: String(data.get('tokenDisplay') || data.get('token') || '').trim(),
            password: String(data.get('password') || ''),
            confirmPassword: String(data.get('confirmPassword') || ''),
        });

        setMessage(result.message || 'Password reset successful.', true);
        window.setTimeout(() => {
            window.location.href = result.redirectUrl || 'login';
        }, 900);
    };

    const handleLogout = async (button) => {
        setLoading(button, true);
        try {
            const result = await postJson(root.dataset.logoutUrl || 'api/auth/logout', {});
            window.location.href = result.redirectUrl || 'login';
        } catch (error) {
            setMessage(error.message || 'Logout failed.');
            setLoading(button, false);
        }
    };

    root.addEventListener('click', async (event) => {
        const target = event.target.closest('[data-auth-action]');
        if (!target) {
            return;
        }

        const action = target.dataset.authAction;
        if (action === 'toggle-password') {
            const input = document.getElementById(target.dataset.target || '');
            if (input instanceof HTMLInputElement) {
                const visible = input.type === 'text';
                input.type = visible ? 'password' : 'text';
                target.textContent = visible ? 'Show' : 'Hide';
                target.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
            }
        }

        if (action === 'logout') {
            await handleLogout(target);
        }
    });

    const form = getForm();
    if (form) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearErrors();

            const button = form.querySelector('[type="submit"]');
            setLoading(button, true);

            try {
                if (mode === 'login') {
                    await handleLogin(form);
                } else if (mode === 'forgot') {
                    await handleForgot(form);
                } else if (mode === 'reset') {
                    await handleReset(form);
                }
            } catch (error) {
                showErrors((error.result && error.result.errors) || {});
                setMessage(error.message || 'Request failed.');
            } finally {
                setLoading(button, false);
            }
        });
    }
})();
