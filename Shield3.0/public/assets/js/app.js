(() => {
    const root = document.getElementById('shield-app');
    if (!root) {
        return;
    }

    const submitUrl = root.dataset.submitUrl || 'api/auth/register';
    const csrfUrl = root.dataset.csrfUrl || 'api/csrf-token';
    const stepperEl = document.getElementById('stepper');
    const contentEl = document.getElementById('form-content');
    const navEl = document.getElementById('nav-footer');
    const activeVectorEl = document.getElementById('active-vector');
    const formEl = document.getElementById('registration-form');

    const servicesList = [
        'SOC Services',
        'Managed Detection & Response',
        'Vulnerability Management',
        'Incident Response',
        'Compliance Services',
        'Risk Assessment',
        'Cloud Security',
        'Endpoint Security',
        'Others',
    ];

    const skillsList = [
        'Network Security',
        'Cloud Security',
        'Application Security',
        'SOC Operations',
        'Threat Hunting',
        'Penetration Testing',
        'Compliance',
        'Risk Management',
        'Incident Response',
        'Security Architecture',
        'Others',
    ];

    const personas = [
        {
            id: 'msp',
            title: 'MSP',
            tagline: 'Managed Security Service Provider onboarding.',
            icon: 'shield',
        },
        {
            id: 'company',
            title: 'Company',
            tagline: 'Enterprise security registration.',
            icon: 'building',
        },
        {
            id: 'consultant',
            title: 'IT Consultant',
            tagline: 'Independent cybersecurity consultant registration.',
            icon: 'briefcase',
        },
    ];

    const stepsByPersona = {
        consultant: ['Select Type', 'Consultant Details', 'Skills & Expertise', 'Security Setup', 'Review & Submit'],
        company: ['Select Type', 'Company Details', 'Branches Setup', 'Security Setup', 'Review & Submit'],
        msp: ['Select Type', 'MSP Details', 'Branches Setup', 'Services Offered', 'Security Setup', 'Review & Submit'],
    };

    const emptyAddress = () => ({
        addressLine1: '',
        addressLine2: '',
        landmark: '',
        city: '',
        state: '',
        country: '',
        postalCode: '',
    });

    const emptySecurity = () => ({
        password: '',
        confirmPassword: '',
    });

    const defaultForms = () => ({
        msp: {
            mspName: '',
            businessName: '',
            website: '',
            contactEmail: '',
            contactNumber: '',
            gstin: '',
            registeredAddress: emptyAddress(),
            branches: [],
            shieldLocationType: 'registered_office',
            subscriptionBranchId: '',
            billingAddressType: 'registered_office',
            services: [],
            customServices: '',
            security: emptySecurity(),
        },
        company: {
            companyName: '',
            registrationNumber: '',
            industry: '',
            website: '',
            contactEmail: '',
            contactNumber: '',
            gstin: '',
            registeredAddress: emptyAddress(),
            branches: [],
            shieldLocationType: 'registered_office',
            subscriptionBranchId: '',
            billingAddressType: 'registered_office',
            security: emptySecurity(),
        },
        consultant: {
            fullName: '',
            email: '',
            mobileNumber: '',
            website: '',
            linkedIn: '',
            address: emptyAddress(),
            expertise: [],
            customExpertise: '',
            gstin: '',
            cvName: '',
            security: emptySecurity(),
        },
    });

    const state = {
        persona: null,
        step: 0,
        forms: defaultForms(),
        errors: {},
        submitting: false,
        submitError: '',
        submitted: null,
        csrfToken: '',
        cvFile: null,
        visiblePasswords: {},
    };

    const iconPaths = {
        shield: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>',
        user: '<path d="M20 21a8 8 0 0 0-16 0"></path><circle cx="12" cy="7" r="4"></circle>',
        userCheck: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="m16 11 2 2 4-4"></path>',
        building: '<rect x="4" y="2" width="16" height="20" rx="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M8 10h.01"></path><path d="M16 10h.01"></path><path d="M8 14h.01"></path><path d="M16 14h.01"></path>',
        briefcase: '<rect x="2" y="7" width="20" height="14" rx="2"></rect><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"></path><path d="M2 12h20"></path>',
        cpu: '<rect x="5" y="5" width="14" height="14" rx="2"></rect><path d="M9 9h6v6H9z"></path><path d="M9 1v4"></path><path d="M15 1v4"></path><path d="M9 19v4"></path><path d="M15 19v4"></path><path d="M1 9h4"></path><path d="M1 15h4"></path><path d="M19 9h4"></path><path d="M19 15h4"></path>',
        check: '<path d="m20 6-11 11-5-5"></path>',
        checkCircle: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path>',
        arrowLeft: '<path d="m12 19-7-7 7-7"></path><path d="M19 12H5"></path>',
        chevronRight: '<path d="m9 18 6-6-6-6"></path>',
        trash: '<path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path>',
        plus: '<path d="M12 5v14"></path><path d="M5 12h14"></path>',
        lock: '<rect x="3" y="11" width="18" height="11" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>',
        info: '<circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4"></path><path d="M12 8h.01"></path>',
        mapPin: '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0z"></path><circle cx="12" cy="10" r="3"></circle>',
        calculator: '<rect x="4" y="2" width="16" height="20" rx="2"></rect><path d="M8 6h8"></path><path d="M8 10h.01"></path><path d="M12 10h.01"></path><path d="M16 10h.01"></path><path d="M8 14h.01"></path><path d="M12 14h.01"></path><path d="M16 14h.01"></path><path d="M8 18h.01"></path><path d="M12 18h.01"></path><path d="M16 18h.01"></path>',
        alert: '<circle cx="12" cy="12" r="10"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path>',
        eye: '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"></path><circle cx="12" cy="12" r="3"></circle>',
        eyeOff: '<path d="m2 2 20 20"></path><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path><path d="M9.9 4.2A10.6 10.6 0 0 1 12 4c6 0 10 8 10 8a17.5 17.5 0 0 1-3.2 4.1"></path><path d="M6.6 6.6C3.8 8.5 2 12 2 12s4 8 10 8a10.8 10.8 0 0 0 4.1-.8"></path>',
        edit: '<path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path>',
        layers: '<path d="m12 2 10 5-10 5L2 7l10-5z"></path><path d="m2 17 10 5 10-5"></path><path d="m2 12 10 5 10-5"></path>',
        fileText: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M16 13H8"></path><path d="M16 17H8"></path><path d="M10 9H8"></path>',
        settings: '<path d="M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5z"></path><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1A2 2 0 1 1 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3h.1a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5h.1a1.7 1.7 0 0 0 1.9-.3l.1-.1A2 2 0 1 1 20 7l-.1.1a1.7 1.7 0 0 0-.3 1.9v.1a1.7 1.7 0 0 0 1.5 1h.1a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"></path>',
        award: '<circle cx="12" cy="8" r="6"></circle><path d="M15.5 13 17 22l-5-3-5 3 1.5-9"></path>',
        squareCheck: '<rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="m9 12 2 2 4-4"></path>',
        landmark: '<path d="M3 22h18"></path><path d="M6 18V9"></path><path d="M10 18V9"></path><path d="M14 18V9"></path><path d="M18 18V9"></path><path d="M12 2 3 7h18l-9-5z"></path>',
    };

    const icon = (name, size = '') => (
        `<svg class="svg-icon ${size}" viewBox="0 0 24 24" aria-hidden="true">${iconPaths[name] || iconPaths.shield}</svg>`
    );

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const getSteps = () => {
        if (!state.persona) {
            return ['Select Type', 'Registration Details', 'Review & Complete'];
        }
        return stepsByPersona[state.persona];
    };

    const activeData = () => state.persona ? state.forms[state.persona] : null;

    const getValue = (source, path) => {
        if (!source || !path) {
            return '';
        }
        return path.split('.').reduce((cursor, segment) => {
            if (cursor === undefined || cursor === null) {
                return undefined;
            }
            return cursor[segment];
        }, source);
    };

    const setValue = (source, path, value) => {
        const segments = path.split('.');
        let cursor = source;
        segments.slice(0, -1).forEach((segment) => {
            if (cursor[segment] === undefined || cursor[segment] === null) {
                cursor[segment] = {};
            }
            cursor = cursor[segment];
        });
        cursor[segments[segments.length - 1]] = value;
    };

    const fieldId = (path) => `field_${path.replace(/[^a-zA-Z0-9]+/g, '_')}`;

    const error = (path) => state.errors[path] || '';

    const errorText = (path) => {
        const message = error(path);
        return message ? `<span class="error-text">${escapeHtml(message)}</span>` : '';
    };

    const inputField = (path, label, options = {}) => {
        const data = activeData();
        const value = getValue(data, path) ?? '';
        const type = options.type || 'text';
        const id = fieldId(path);
        const attrs = [
            `id="${id}"`,
            `type="${escapeHtml(type)}"`,
            `data-field="${escapeHtml(path)}"`,
            `value="${escapeHtml(value)}"`,
            options.placeholder ? `placeholder="${escapeHtml(options.placeholder)}"` : '',
            options.min !== undefined ? `min="${escapeHtml(options.min)}"` : '',
            options.max !== undefined ? `max="${escapeHtml(options.max)}"` : '',
            options.disabled ? 'disabled' : '',
        ].filter(Boolean).join(' ');

        return `
            <div class="field ${error(path) ? 'has-error' : ''} ${options.className || ''}">
                <label for="${id}">
                    <span>${escapeHtml(label)}</span>
                    ${options.required ? '<span class="required-mark">* Required</span>' : ''}
                </label>
                <input ${attrs}>
                ${errorText(path)}
                ${options.helper ? `<span class="helper-text">${escapeHtml(options.helper)}</span>` : ''}
            </div>
        `;
    };

    const fileField = (path, label, options = {}) => {
        const id = fieldId(path);
        return `
            <div class="field ${error(path) ? 'has-error' : ''} ${options.className || ''}">
                <label for="${id}">
                    <span>${escapeHtml(label)}</span>
                    ${options.required ? '<span class="required-mark">* Required</span>' : ''}
                </label>
                <input
                    id="${id}"
                    name="${escapeHtml(options.name || path)}"
                    type="file"
                    data-file-field="${escapeHtml(path)}"
                    accept="${escapeHtml(options.accept || '.txt,.pdf,.doc,.docx')}"
                >
                ${state.cvFile ? `<span class="helper-text">Selected: ${escapeHtml(state.cvFile.name)} (${Math.round(state.cvFile.size / 1024)} KB)</span>` : ''}
                ${errorText(path)}
                ${options.helper ? `<span class="helper-text">${escapeHtml(options.helper)}</span>` : ''}
            </div>
        `;
    };

    const passwordField = (path, label, options = {}) => {
        const data = activeData();
        const value = getValue(data, path) ?? '';
        const id = fieldId(path);
        const visible = !!state.visiblePasswords[path];
        const requirements = [
            ['Minimum 8 characters', value.length >= 8],
            ['One uppercase letter', /[A-Z]/.test(value)],
            ['One lowercase letter', /[a-z]/.test(value)],
            ['One number', /[0-9]/.test(value)],
            ['One special character', /[^A-Za-z0-9]/.test(value)],
        ];

        return `
            <div class="field ${error(path) ? 'has-error' : ''}">
                <label for="${id}">
                    <span>${escapeHtml(label)}</span>
                    <span class="required-mark">* Required</span>
                </label>
                <div class="password-wrap">
                    <input
                        id="${id}"
                        type="${visible ? 'text' : 'password'}"
                        data-field="${escapeHtml(path)}"
                        value="${escapeHtml(value)}"
                        placeholder="${escapeHtml(options.placeholder || 'Password')}"
                    >
                    <button class="icon-button" type="button" data-action="toggle-password" data-path="${escapeHtml(path)}" aria-label="${visible ? 'Hide password' : 'Show password'}">
                        ${icon(visible ? 'eyeOff' : 'eye')}
                    </button>
                </div>
                ${errorText(path)}
                ${options.requirements && value ? `
                    <div class="requirements">
                        <strong>Password Complexity Requirements:</strong>
                        <div class="requirements-grid">
                            ${requirements.map(([labelText, met]) => `
                                <div class="requirement ${met ? 'is-met' : ''}">
                                    ${icon(met ? 'checkCircle' : 'alert', 'sm')}
                                    <span>${escapeHtml(labelText)}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    };

    const selectField = (path, label, options, config = {}) => {
        const data = activeData();
        const value = getValue(data, path) ?? '';
        const id = fieldId(path);
        return `
            <div class="field ${error(path) ? 'has-error' : ''} ${config.className || ''}">
                <label for="${id}"><span>${escapeHtml(label)}</span></label>
                <select id="${id}" data-field="${escapeHtml(path)}" class="${error(path) ? 'has-error' : ''}" ${config.disabled ? 'disabled' : ''}>
                    ${config.placeholder ? `<option value="">${escapeHtml(config.placeholder)}</option>` : ''}
                    ${options.map((item) => `
                        <option value="${escapeHtml(item.value)}" ${item.value === value ? 'selected' : ''}>${escapeHtml(item.label)}</option>
                    `).join('')}
                </select>
                ${errorText(path)}
            </div>
        `;
    };

    const checkboxGroup = (path, items) => {
        const selected = getValue(activeData(), path) || [];
        return items.map((item) => {
            const checked = selected.includes(item);
            return `
                <label class="checkbox-card ${checked ? 'is-checked' : ''}">
                    <input type="checkbox" data-array-field="${escapeHtml(path)}" value="${escapeHtml(item)}" ${checked ? 'checked' : ''}>
                    <span>${escapeHtml(item)}</span>
                </label>
            `;
        }).join('');
    };

    const addressFields = (prefix, title, includeLandmark = true) => `
        <section>
            <h3 class="subsection-title">${escapeHtml(title)}</h3>
            <div class="form-grid">
                <div class="span-2">${inputField(`${prefix}.addressLine1`, 'Address Line 1', { required: true, placeholder: 'House / Building No., Street, Area' })}</div>
                <div class="span-2">${inputField(`${prefix}.addressLine2`, 'Address Line 2', { placeholder: 'Apartment, Suite, Unit, etc. (Optional)' })}</div>
                ${includeLandmark ? inputField(`${prefix}.landmark`, 'Landmark (Optional)', { placeholder: 'E.g. Near City Mall' }) : ''}
                ${inputField(`${prefix}.city`, 'City', { required: true, placeholder: 'City' })}
                ${inputField(`${prefix}.state`, 'State / Province', { required: true, placeholder: 'State / Province' })}
                ${inputField(`${prefix}.country`, 'Country', { required: true, placeholder: 'Country' })}
                <div class="span-2">${inputField(`${prefix}.postalCode`, 'Postal / ZIP Code', { required: true, placeholder: 'Postal / ZIP Code' })}</div>
            </div>
        </section>
    `;

    const renderPersonaSelector = () => `
        <div>
            <header class="section-head center">
                <h2>Select Registration Type</h2>
                <p>Choose the registration persona that best fits your business structure or professional capacity to configure your Kenexoft SHIELD setup.</p>
            </header>
            <div class="persona-grid">
                ${personas.map((persona) => `
                    <button type="button" class="persona-card ${state.persona === persona.id ? 'is-selected' : ''}" data-select-persona="${persona.id}">
                        ${state.persona === persona.id ? '<span class="selected-mark">&check;</span>' : ''}
                        <span class="persona-icon">${icon(persona.icon)}</span>
                        <span>
                            <h3>${escapeHtml(persona.title)}</h3>
                            <p>${escapeHtml(persona.tagline)}</p>
                        </span>
                    </button>
                `).join('')}
            </div>
        </div>
    `;

    const renderConsultantInfo = () => `
        <div class="form-stack">
            <header class="section-head">
                <h2>${icon('briefcase')}Consultant Profile Information</h2>
                <p>Provide your consultant identity, portfolio sites, and home address.</p>
            </header>
            <div class="form-grid">
                ${inputField('fullName', 'Full Name', { required: true, placeholder: 'E.g. Dr. Jane Smith' })}
                ${inputField('email', 'Email Address', { required: true, type: 'email', placeholder: 'jane.smith@consultant.com' })}
                ${inputField('mobileNumber', 'Mobile Number', { required: true, type: 'tel', placeholder: 'E.g. 9876543210' })}
                ${inputField('website', 'Website / Portfolio (Optional)', { type: 'url', placeholder: 'https://janesmith.security' })}
                ${inputField('linkedIn', 'LinkedIn Profile (Optional)', { type: 'url', placeholder: 'https://linkedin.com/in/janesmith' })}
                ${inputField('gstin', 'GSTIN Number (Optional)', { placeholder: 'Indian GSTIN format (e.g. 22AAAAA1111A1Z5)' })}
            </div>
            ${addressFields('address', 'Residential Address')}
            ${fileField('cv', 'CV / Resume', {
                name: 'cv',
                required: true,
                helper: 'Accepted formats: TXT, PDF, DOC, DOCX. Maximum size: 10 MB.',
            })}
        </div>
    `;

    const renderConsultantSkills = () => {
        const selected = activeData().expertise || [];
        return `
            <div class="form-stack">
                <header class="section-head">
                    <h2>${icon('award')}Expertise &amp; Cybersecurity Skills</h2>
                    <p>Select the security competencies you offer as a specialized consultant.</p>
                </header>
                <section>
                    <label class="subsection-title">Select Areas of Expertise (Select at least one)</label>
                    <div class="choice-grid">${checkboxGroup('expertise', skillsList)}</div>
                    ${errorText('expertise')}
                    ${selected.includes('Others') ? `
                        <div class="conditional-field">
                            ${inputField('customExpertise', 'Provide Custom Expertise', { required: true, placeholder: 'Describe your expertise, e.g. Quantum Cryptography' })}
                        </div>
                    ` : ''}
                </section>
            </div>
        `;
    };

    const renderCompanyInfo = () => `
        <div class="form-stack">
            <header class="section-head">
                <h2>${icon('building')}Company Information</h2>
                <p>Enter your company profile, corporate registration details, and official office address.</p>
            </header>
            <div class="form-grid">
                ${inputField('companyName', 'Company Name', { required: true, placeholder: 'E.g. Kenexoft Technologies' })}
                ${inputField('registrationNumber', 'Registration Number', { required: true, placeholder: 'E.g. CIN U72200KA2021PTC123456' })}
                ${inputField('industry', 'Industry', { required: true, placeholder: 'E.g. Financial Services / Healthcare' })}
                ${inputField('website', 'Website', { required: true, type: 'url', placeholder: 'https://example.com' })}
                ${inputField('contactEmail', 'Contact Email', { required: true, type: 'email', placeholder: 'contact@company.com' })}
                ${inputField('contactNumber', 'Contact Number', { required: true, type: 'tel', placeholder: 'E.g. 9876543210' })}
                <div class="span-2">${inputField('gstin', 'GSTIN Number (Optional)', { placeholder: 'Indian GSTIN format (e.g. 22AAAAA1111A1Z5)' })}</div>
            </div>
            ${addressFields('registeredAddress', 'Registered Office Address')}
        </div>
    `;

    const renderMspInfo = () => `
        <div class="form-stack">
            <header class="section-head">
                <h2>${icon('shield')}MSP Company Information</h2>
                <p>Enter corporate profile details and official registered address.</p>
            </header>
            <div class="form-grid">
                ${inputField('mspName', 'MSP Name', { required: true, placeholder: 'E.g. Apex CyberMSP' })}
                ${inputField('businessName', 'Registered Business Name', { required: true, placeholder: 'E.g. Apex Security Solutions Pvt Ltd' })}
                ${inputField('website', 'Website', { required: true, type: 'url', placeholder: 'https://example.com' })}
                ${inputField('contactEmail', 'Contact Email', { required: true, type: 'email', placeholder: 'contact@example.com' })}
                ${inputField('contactNumber', 'Contact Number', { required: true, type: 'tel', placeholder: 'E.g. 9876543210' })}
                ${inputField('gstin', 'GSTIN Number (Optional)', { placeholder: 'Indian GSTIN format (e.g. 22AAAAA1111A1Z5)' })}
            </div>
            ${addressFields('registeredAddress', 'Registered Office Address')}
        </div>
    `;

    const formatAddress = (address) => {
        if (!address || !address.addressLine1) {
            return 'No Address details set yet. Ensure the address step is filled.';
        }
        const line2 = address.addressLine2 ? `, ${address.addressLine2}` : '';
        const landmark = address.landmark ? ` (Landmark: ${address.landmark})` : '';
        return `${address.addressLine1}${line2}${landmark}, ${address.city}, ${address.state}, ${address.country} - ${address.postalCode}`;
    };

    const branchToAddress = (branch) => branch ? {
        addressLine1: branch.addressLine1 || '',
        addressLine2: branch.addressLine2 || '',
        city: branch.city || '',
        state: branch.state || '',
        country: branch.country || '',
        postalCode: branch.postalCode || '',
    } : undefined;

    const getAddressContext = () => {
        const data = activeData();
        if (!state.persona || !data) {
            return {};
        }

        if (state.persona === 'consultant') {
            return {
                registeredAddress: data.address,
                subscriptionAddress: undefined,
                billingAddress: data.address,
                branches: [],
            };
        }

        const branches = data.branches || [];
        const selectedBranch = branches.find((branch) => branch.id === data.subscriptionBranchId);
        const selectedBranchAddress = branchToAddress(selectedBranch);
        const subscriptionAddress = data.shieldLocationType === 'specific_branch' && selectedBranchAddress
            ? selectedBranchAddress
            : data.registeredAddress;
        const billingAddress = data.billingAddressType === 'selected_branch' && subscriptionAddress
            ? subscriptionAddress
            : data.registeredAddress;

        return {
            registeredAddress: data.registeredAddress,
            subscriptionAddress,
            billingAddress,
            branches,
        };
    };

    const renderBranchSetup = (kind) => {
        const data = activeData();
        const branches = data.branches || [];
        const selectedBranch = branches.find((branch) => branch.id === data.subscriptionBranchId);
        const subscriptionDisabled = branches.length === 0;
        const branchBillingDisabled = data.shieldLocationType === 'registered_office' || !data.subscriptionBranchId;
        const billingAddress = data.billingAddressType === 'selected_branch' && selectedBranch
            ? branchToAddress(selectedBranch)
            : data.registeredAddress;
        const title = kind === 'msp' ? 'Branches & Operations Config' : 'Branches & Installation Setup';
        const description = kind === 'msp'
            ? 'Configure physical branches, select SHIELD subscription installation, and assign billing locations.'
            : 'Organize branches, locate the primary SHIELD software node, and select a billing target.';

        return `
            <div class="form-stack">
                <header class="section-head">
                    <h2>${icon('settings')}${title}</h2>
                    <p>${description}</p>
                </header>

                <section>
                    <div class="branch-toolbar">
                        <div>
                            <h3>${icon('building')}Branch Management</h3>
                            <p>Add your branches to configure SHIELD localized subscriptions and billing.</p>
                        </div>
                        <div class="branch-count">
                            <div class="field">
                                <label for="branch_count"><span>Number of Branches</span></label>
                                <input id="branch_count" type="number" min="0" max="25" data-branch-count value="${branches.length}">
                            </div>
                        </div>
                    </div>

                    ${branches.length > 0 ? `
                        <div class="branch-list">
                            ${branches.map((branch, index) => `
                                <article class="branch-card">
                                    <div class="branch-head">
                                        <input class="branch-name-input" data-field="branches.${index}.branchName" value="${escapeHtml(branch.branchName || '')}" placeholder="Branch Office ${index + 1}">
                                        <button type="button" class="icon-button" data-action="remove-branch" data-index="${index}" title="Remove Branch">${icon('trash', 'sm')}</button>
                                    </div>
                                    ${errorText(`branches.${index}.branchName`)}
                                    <div class="form-grid">
                                        <div class="span-2">${inputField(`branches.${index}.addressLine1`, 'Address Line 1', { required: true, placeholder: 'Street address, P.O. box' })}</div>
                                        <div class="span-2">${inputField(`branches.${index}.addressLine2`, 'Address Line 2', { placeholder: 'Apartment, suite, unit (Optional)' })}</div>
                                        ${inputField(`branches.${index}.city`, 'City', { required: true, placeholder: 'City' })}
                                        ${inputField(`branches.${index}.state`, 'State', { required: true, placeholder: 'State / Province' })}
                                        ${inputField(`branches.${index}.country`, 'Country', { required: true, placeholder: 'Country' })}
                                        ${inputField(`branches.${index}.postalCode`, 'Postal Code', { required: true, placeholder: 'ZIP / Postal Code' })}
                                    </div>
                                </article>
                            `).join('')}
                        </div>
                    ` : `
                        <div class="empty-state">
                            ${icon('building', 'lg')}
                            <h4>No branches registered</h4>
                            <p>If your business operates from multiple locations, add them above.</p>
                            <button type="button" class="btn btn-muted" data-action="add-branch">${icon('plus', 'sm')}Add First Branch</button>
                        </div>
                    `}
                </section>

                <section class="soft-panel">
                    <h3>SHIELD Subscription Location</h3>
                    <p>Is Kenexoft SHIELD registration for?</p>
                    <div class="option-row">
                        <label class="radio-label">
                            <input type="radio" data-field="shieldLocationType" value="registered_office" ${data.shieldLocationType === 'registered_office' ? 'checked' : ''}>
                            Registered Office
                        </label>
                        <label class="radio-label">
                            <input type="radio" data-field="shieldLocationType" value="specific_branch" ${data.shieldLocationType === 'specific_branch' ? 'checked' : ''} ${subscriptionDisabled ? 'disabled' : ''}>
                            Specific Branch ${subscriptionDisabled ? '<small>(Add a branch first)</small>' : ''}
                        </label>
                    </div>
                    ${data.shieldLocationType === 'specific_branch' && branches.length > 0 ? `
                        ${selectField('subscriptionBranchId', 'Select Installation Branch', branches.map((branch) => ({
                            value: branch.id,
                            label: `${branch.branchName || 'Branch'} (${branch.city || 'City'}, ${branch.state || 'State'})`,
                        })), { placeholder: '-- Choose Branch --', className: 'branch-count' })}
                    ` : ''}
                </section>

                <section class="soft-panel">
                    <h3>Billing Address Selection</h3>
                    <p>Which address should be used for billing?</p>
                    <div class="radio-stack">
                        <label class="radio-label">
                            <input type="radio" data-field="billingAddressType" value="registered_office" ${data.billingAddressType === 'registered_office' ? 'checked' : ''}>
                            ${kind === 'msp' ? 'Use Registered Office Address for Billing' : 'Use Registered Office Address'}
                        </label>
                        <label class="radio-label">
                            <input type="radio" data-field="billingAddressType" value="selected_branch" ${data.billingAddressType === 'selected_branch' ? 'checked' : ''} ${branchBillingDisabled ? 'disabled' : ''}>
                            ${kind === 'msp' ? 'Use Selected Branch Address for Billing' : 'Use Selected Branch Address'}
                            ${branchBillingDisabled ? '<small>(Available only for Specific Branch setup)</small>' : ''}
                        </label>
                    </div>
                    <div class="summary-address">
                        ${icon('mapPin')}
                        <div>
                            <strong>${kind === 'msp' ? 'Selected Billing Address:' : 'Billing Location Summary:'}</strong>
                            <p>${escapeHtml(formatAddress(billingAddress))}</p>
                        </div>
                    </div>
                </section>
            </div>
        `;
    };

    const renderMspServices = () => {
        const selected = activeData().services || [];
        return `
            <div class="form-stack">
                <header class="section-head">
                    <h2>${icon('squareCheck')}Managed Services Offered</h2>
                    <p>Select all security competencies your practice provides to customers under the MSP license.</p>
                </header>
                <section>
                    <label class="subsection-title">Select Cybersecurity Services (Select at least one)</label>
                    <div class="choice-grid">${checkboxGroup('services', servicesList)}</div>
                    ${errorText('services')}
                    ${selected.includes('Others') ? `
                        <div class="conditional-field">
                            ${inputField('customServices', 'Provide Custom Service Entry', { required: true, placeholder: 'Describe custom service, e.g. Blockchain Auditing' })}
                        </div>
                    ` : ''}
                </section>
            </div>
        `;
    };

    const normalised = (value) => String(value || '').trim().toLowerCase();

    const calculateGST = (registered, subscription, billing) => {
        const defaultResult = {
            taxType: 'CGST + SGST',
            category: 'Same State',
            details: 'Calculated default CGST + SGST (Same State) tax.',
        };

        if (!registered || !billing) {
            return defaultResult;
        }

        const subscriptionAddress = subscription || registered;
        const regCountry = normalised(registered.country);
        const regState = normalised(registered.state);
        const subCountry = normalised(subscriptionAddress.country);
        const subState = normalised(subscriptionAddress.state);
        const billCountry = normalised(billing.country);
        const billState = normalised(billing.state);

        if (regCountry !== billCountry || regCountry !== subCountry || billCountry !== subCountry) {
            return {
                taxType: 'IGST',
                category: 'Different Country',
                details: 'IGST applied due to cross-country addresses.',
            };
        }

        if (regState === billState && regState === subState) {
            return {
                taxType: 'CGST + SGST',
                category: 'Same State',
                details: `CGST + SGST applied: Supplier, Subscriber, and Billing addresses are in the same state (${registered.state || 'N/A'}).`,
            };
        }

        return {
            taxType: 'IGST',
            category: 'Different State',
            details: `IGST applied: Inter-state transactions between ${registered.state || 'N/A'} (Registered), ${subscriptionAddress.state || 'N/A'} (Subscription), and ${billing.state || 'N/A'} (Billing).`,
        };
    };

    const taxBadgeClass = (category) => {
        if (category === 'Same State') {
            return 'same';
        }
        return category === 'Different State' ? 'state' : 'country';
    };

    const renderTaxCard = (gstin = '') => {
        const { registeredAddress, subscriptionAddress, billingAddress } = getAddressContext();
        const hasRegistered = !!(registeredAddress && registeredAddress.city && registeredAddress.state && registeredAddress.country);
        const hasBilling = !!(billingAddress && billingAddress.city && billingAddress.state && billingAddress.country);
        const classification = calculateGST(registeredAddress, subscriptionAddress, billingAddress);
        const regState = normalised(registeredAddress && registeredAddress.state);
        const subState = normalised((subscriptionAddress && subscriptionAddress.state) || (registeredAddress && registeredAddress.state));
        const billState = normalised(billingAddress && billingAddress.state);
        const regCountry = normalised(registeredAddress && registeredAddress.country);
        const subCountry = normalised((subscriptionAddress && subscriptionAddress.country) || (registeredAddress && registeredAddress.country));
        const billCountry = normalised(billingAddress && billingAddress.country);
        const sameState = regState && regState === subState && regState === billState;
        const sameCountry = regCountry && regCountry === subCountry && regCountry === billCountry;

        return `
            <article class="tax-card">
                <div class="tax-title">${icon('calculator')}GST Classification Preview</div>
                ${!hasRegistered || !hasBilling ? `
                    <div class="tax-empty">
                        ${icon('alert', 'lg')}
                        <p>Provide Registered Office and Billing address details to calculate live tax classifications.</p>
                    </div>
                ` : `
                    <div class="tax-badge-row">
                        <div>
                            <p>Calculated Tax Class</p>
                            <h3>${escapeHtml(classification.taxType)}</h3>
                        </div>
                        <span class="badge ${taxBadgeClass(classification.category)}">${escapeHtml(classification.category)}</span>
                    </div>
                    <div class="relation-grid">
                        <div class="relation-card">
                            ${icon('checkCircle', 'sm')}
                            <div><p>State Relation</p><strong>${sameState ? 'Same State' : 'Different State'}</strong></div>
                        </div>
                        <div class="relation-card">
                            ${icon('checkCircle', 'sm')}
                            <div><p>Country Relation</p><strong>${sameCountry ? 'Same Country' : 'Different Country'}</strong></div>
                        </div>
                    </div>
                    <p class="tax-details">${escapeHtml(classification.details)}</p>
                    ${gstin ? `<div class="tax-pair"><span>Verified GSTIN:</span><strong>${escapeHtml(gstin.toUpperCase())}</strong></div>` : ''}
                `}
            </article>
        `;
    };

    const renderSecurity = () => {
        const data = activeData();
        const emailValue = state.persona === 'msp' || state.persona === 'company' ? data.contactEmail : data.email;
        return `
            <div class="form-stack">
                <header class="section-head">
                    <h2>${icon('lock')}Security Credentials</h2>
                    <p>Establish secure administrative access to the Kenexoft SHIELD control console.</p>
                </header>
                <div class="security-grid">
                    <section class="form-stack">
                        <div class="notice">
                            ${icon('info')}
                            <div>
                                <strong>Console Username</strong>
                                <p>The email address you provided can be used as your Username to log in:</p>
                                <span class="code-chip">${escapeHtml(emailValue || '[Email address not entered yet]')}</span>
                            </div>
                        </div>
                        ${passwordField('security.password', 'Password', { requirements: true, placeholder: 'Password' })}
                        ${passwordField('security.confirmPassword', 'Confirm Password', { placeholder: 'Confirm password' })}
                    </section>
                    <aside class="form-stack">
                        <div class="overline">${icon('landmark', 'sm')} Tax Compliance</div>
                        ${renderTaxCard(data.gstin || '')}
                    </aside>
                </div>
            </div>
        `;
    };

    const personaLabel = () => {
        if (state.persona === 'msp') {
            return ['Managed Service Provider (MSP)', 'shield'];
        }
        if (state.persona === 'company') {
            return ['Enterprise Company', 'building'];
        }
        return ['Independent IT Consultant', 'briefcase'];
    };

    const detailsForReview = () => {
        const data = activeData();
        if (state.persona === 'msp') {
            return {
                title: 'Company Details',
                items: [
                    ['MSP Name', data.mspName || 'N/A'],
                    ['Registered Business Name', data.businessName || 'N/A'],
                    ['Website', data.website || 'N/A'],
                    ['Contact Email', data.contactEmail || 'N/A'],
                    ['Contact Number', data.contactNumber || 'N/A'],
                    ['Managed Services', [...(data.services || []), ...(data.customServices ? [data.customServices] : [])].join(', ') || 'N/A'],
                ],
            };
        }
        if (state.persona === 'company') {
            return {
                title: 'Enterprise Details',
                items: [
                    ['Company Name', data.companyName || 'N/A'],
                    ['Registration Number', data.registrationNumber || 'N/A'],
                    ['Industry', data.industry || 'N/A'],
                    ['Website', data.website || 'N/A'],
                    ['Contact Email', data.contactEmail || 'N/A'],
                    ['Contact Number', data.contactNumber || 'N/A'],
                ],
            };
        }
        return {
            title: 'Consultant Information',
            items: [
                ['Full Name', data.fullName || 'N/A'],
                ['Email Address', data.email || 'N/A'],
                ['Mobile Number', data.mobileNumber || 'N/A'],
                ['Website / Portfolio', data.website || 'N/A'],
                ['LinkedIn Profile', data.linkedIn || 'N/A'],
                ['Expertise & Skills', [...(data.expertise || []), ...(data.customExpertise ? [data.customExpertise] : [])].join(', ') || 'N/A'],
            ],
        };
    };

    const renderReview = () => {
        const data = activeData();
        const [label, labelIcon] = personaLabel();
        const details = detailsForReview();
        const context = getAddressContext();
        const branches = context.branches || [];
        const classification = calculateGST(context.registeredAddress, context.subscriptionAddress, context.billingAddress);
        const email = data.email || data.contactEmail || '';

        return `
            <div class="form-stack">
                <header class="section-head">
                    <h2>Review &amp; Submit Registration</h2>
                    <p>Please verify all details before submitting. You can edit any step by clicking the edit icon.</p>
                </header>
                <div class="review-grid">
                    <div class="review-column">
                        <article class="card persona-review">
                            <span class="round-icon">${icon(labelIcon)}</span>
                            <div>
                                <span class="overline">Selected Profile</span>
                                <h3>${escapeHtml(label)}</h3>
                            </div>
                            <button type="button" class="icon-button" data-action="edit-step" data-step="0" title="Change Persona">${icon('edit', 'sm')}</button>
                        </article>

                        <article class="card">
                            <div class="card-head">
                                <h3>${icon('layers', 'sm')}${escapeHtml(details.title)}</h3>
                                <button type="button" class="icon-button" data-action="edit-step" data-step="1" title="Edit Details">${icon('edit', 'sm')}</button>
                            </div>
                            <dl class="details-list">
                                ${details.items.map(([itemLabel, itemValue]) => `
                                    <div class="${String(itemValue).length > 30 ? 'wide' : ''}">
                                        <dt>${escapeHtml(itemLabel)}</dt>
                                        <dd>${escapeHtml(itemValue)}</dd>
                                    </div>
                                `).join('')}
                            </dl>
                        </article>

                        <article class="card">
                            <div class="card-head">
                                <h3>${icon('mapPin', 'sm')}Address Details</h3>
                                <button type="button" class="icon-button" data-action="edit-step" data-step="1" title="Edit Address">${icon('edit', 'sm')}</button>
                            </div>
                            <div class="address-block">
                                <div>
                                    <h4>${state.persona === 'consultant' ? 'Residence Address' : 'Registered Office Address'}</h4>
                                    <p>${escapeHtml(formatAddress(context.registeredAddress))}</p>
                                </div>
                                ${state.persona === 'msp' || state.persona === 'company' ? `
                                    <div class="divider-top">
                                        <h4>SHIELD Subscription Location</h4>
                                        <p>${data.shieldLocationType === 'registered_office'
                                            ? 'Registered Office Address'
                                            : `Specific Branch: ${(branches.find((branch) => branch.id === data.subscriptionBranchId) || {}).branchName || 'Unknown Branch'}`
                                        }</p>
                                    </div>
                                    <div class="divider-top">
                                        <h4>Billing Address Location</h4>
                                        <p>${data.billingAddressType === 'registered_office' ? 'Registered Office Address' : 'Subscription Branch Office Address'}</p>
                                        <div class="inline-address">${escapeHtml(formatAddress(context.billingAddress))}</div>
                                    </div>
                                ` : ''}
                            </div>
                        </article>

                        ${(state.persona === 'msp' || state.persona === 'company') && branches.length > 0 ? `
                            <article class="card">
                                <div class="card-head">
                                    <h3>${icon('building', 'sm')}Registered Branches (${branches.length})</h3>
                                    <button type="button" class="icon-button" data-action="edit-step" data-step="2" title="Edit Branches">${icon('edit', 'sm')}</button>
                                </div>
                                <div class="branch-review-list">
                                    ${branches.map((branch) => `
                                        <div class="branch-review-item">
                                            <strong>${escapeHtml(branch.branchName || 'Branch Office')}</strong>
                                            <span>${escapeHtml(`${branch.addressLine1 || ''}, ${branch.city || ''}, ${branch.state || ''}, ${branch.country || ''} - ${branch.postalCode || ''}`)}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            </article>
                        ` : ''}
                    </div>

                    <aside class="review-column">
                        <article class="tax-summary">
                            <div class="tax-title">${icon('fileText', 'sm')}GST Classification Card</div>
                            <div class="tax-badge-row">
                                <div>
                                    <p>Category</p>
                                    <strong>${escapeHtml(classification.category)}</strong>
                                </div>
                                <span class="badge ${taxBadgeClass(classification.category)}">${escapeHtml(classification.taxType)}</span>
                            </div>
                            <div class="tax-pair"><span>GSTIN Number:</span><strong>${escapeHtml((data.gstin || 'Not Provided').toUpperCase())}</strong></div>
                            <div class="tax-pair"><span>State Supply:</span><strong>${escapeHtml((context.registeredAddress && context.registeredAddress.state) || 'N/A')} -> ${escapeHtml((context.billingAddress && context.billingAddress.state) || 'N/A')}</strong></div>
                            <div class="tax-pair"><span>Country supply:</span><strong>${escapeHtml((context.registeredAddress && context.registeredAddress.country) || 'N/A')}</strong></div>
                            <p class="mono-note">${escapeHtml(classification.details)}</p>
                        </article>

                        <div class="credential-notice">
                            ${icon('lock')}
                            <div>
                                <strong>Security Credentials Verified</strong>
                                <p>Credentials are set. The username for this account will be your email: <u>${escapeHtml(email)}</u></p>
                            </div>
                        </div>

                        <article class="card submission-card">
                            ${icon('checkCircle', 'lg')}
                            <div>
                                <h4>Verification Success</h4>
                                <p>All data fields passed integrity check</p>
                            </div>
                            <button type="button" class="btn btn-green" data-action="submit-registration" ${state.submitting ? 'disabled' : ''}>
                                ${state.submitting ? '<span class="spinner"></span>Submitting to SHIELD...' : 'Submit Registration'}
                            </button>
                            ${state.submitError ? `<p class="submit-error">${escapeHtml(state.submitError)}</p>` : ''}
                        </article>
                    </aside>
                </div>
            </div>
        `;
    };

    const renderStepContent = () => {
        if (!state.persona || state.step === 0) {
            return renderPersonaSelector();
        }

        const steps = getSteps();
        const reviewStep = steps.length - 1;
        const securityStep = steps.length - 2;

        if (state.step === reviewStep) {
            return renderReview();
        }

        if (state.step === securityStep) {
            return renderSecurity();
        }

        if (state.persona === 'consultant') {
            return state.step === 1 ? renderConsultantInfo() : renderConsultantSkills();
        }

        if (state.persona === 'company') {
            return state.step === 1 ? renderCompanyInfo() : renderBranchSetup('company');
        }

        if (state.persona === 'msp') {
            if (state.step === 1) {
                return renderMspInfo();
            }
            return state.step === 2 ? renderBranchSetup('msp') : renderMspServices();
        }

        return '';
    };

    const renderStepper = () => {
        const steps = getSteps();
        stepperEl.hidden = !state.persona;
        if (!state.persona) {
            stepperEl.innerHTML = '';
            return;
        }

        stepperEl.innerHTML = `
            <div class="stepper">
                ${steps.map((step, index) => {
                    const complete = index < state.step;
                    const active = index === state.step;
                    return `
                        <div class="step ${complete ? 'is-complete' : ''} ${active ? 'is-active' : ''}">
                            <span class="step-dot">${complete ? icon('check', 'sm') : index + 1}</span>
                            <span class="step-label">${escapeHtml(step)}</span>
                        </div>
                        ${index < steps.length - 1 ? `<div class="step-line ${index < state.step ? 'is-complete' : ''}"></div>` : ''}
                    `;
                }).join('')}
            </div>
        `;
    };

    const renderActiveVector = () => {
        if (!state.persona) {
            activeVectorEl.className = 'metric-empty';
            activeVectorEl.innerHTML = 'Awaiting persona selection to bind node configurations.';
            return;
        }

        const steps = getSteps();
        const percent = Math.round((state.step / (steps.length - 1)) * 100);
        const personaInfo = personas.find((item) => item.id === state.persona);
        activeVectorEl.className = 'metric-card';
        activeVectorEl.innerHTML = `
            <div class="metric-heading">${icon(personaInfo.icon, 'sm')}<span>${escapeHtml(state.persona)} Node Onboarding</span></div>
            <div class="progress-track"><div class="progress-bar" style="width:${percent}%"></div></div>
            <div class="metric-meta"><span>Step ${state.step + 1} of ${steps.length}</span><span>${percent}% Complete</span></div>
        `;
    };

    const renderNav = () => {
        const steps = getSteps();
        const reviewStep = steps.length - 1;
        navEl.hidden = state.step === 0;

        if (state.step === 0) {
            navEl.innerHTML = '';
            return;
        }

        navEl.innerHTML = `
            <button type="button" class="btn btn-muted" data-action="back">${icon('arrowLeft', 'sm')}Back</button>
            ${state.step === reviewStep ? '' : `<button type="button" class="btn btn-primary" data-action="next">Next Step${icon('chevronRight', 'sm')}</button>`}
        `;
    };

    const render = (scroll = false) => {
        renderStepper();
        renderActiveVector();
        contentEl.innerHTML = renderStepContent();
        renderNav();

        if (scroll) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    };

    const addError = (path, message) => {
        state.errors[path] = message;
    };

    const required = (data, path, message) => {
        const value = getValue(data, path);
        if (String(value || '').trim() === '') {
            addError(path, message);
            return false;
        }
        return true;
    };

    const validateEmail = (data, path) => {
        if (!required(data, path, 'Email address is required')) {
            return;
        }
        const value = String(getValue(data, path) || '').trim();
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            addError(path, 'Invalid email address');
        }
    };

    const validatePhone = (data, path) => {
        if (!required(data, path, 'Phone number is required')) {
            return;
        }
        const value = String(getValue(data, path) || '').trim();
        if (!/^\+?[0-9\s-]{8,15}$/.test(value)) {
            addError(path, 'Invalid phone number format');
        }
    };

    const validateUrl = (data, path, message, optional = false) => {
        const value = String(getValue(data, path) || '').trim();
        if (!value) {
            if (!optional) {
                addError(path, 'Website is required');
            }
            return;
        }
        try {
            const url = new URL(value);
            if (!['http:', 'https:'].includes(url.protocol)) {
                addError(path, message);
            }
        } catch (error) {
            addError(path, message);
        }
    };

    const validateGstin = (data, path) => {
        const value = String(getValue(data, path) || '').trim().toUpperCase();
        if (value && !/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/.test(value)) {
            addError(path, 'Invalid Indian GSTIN format (e.g. 22AAAAA1111A1Z5)');
        }
    };

    const validateCv = () => {
        if (!state.cvFile) {
            addError('cv', 'CV/Resume upload is required.');
            return;
        }

        const allowed = ['txt', 'pdf', 'doc', 'docx'];
        const extension = state.cvFile.name.split('.').pop().toLowerCase();
        if (!allowed.includes(extension)) {
            addError('cv', 'CV must be a TXT, PDF, DOC, or DOCX file.');
        }

        if (state.cvFile.size > 10 * 1024 * 1024) {
            addError('cv', 'CV must not exceed 10 MB.');
        }
    };

    const validateAddress = (data, prefix) => {
        required(data, `${prefix}.addressLine1`, 'Address Line 1 is required');
        required(data, `${prefix}.city`, 'City is required');
        required(data, `${prefix}.state`, 'State / Province is required');
        required(data, `${prefix}.country`, 'Country is required');
        required(data, `${prefix}.postalCode`, 'Postal / ZIP Code is required');
    };

    const validateBranches = (data) => {
        (data.branches || []).forEach((branch, index) => {
            required(branch, 'branchName', 'Branch Name is required') || (state.errors[`branches.${index}.branchName`] = state.errors.branchName);
            delete state.errors.branchName;
            required(branch, 'addressLine1', 'Address Line 1 is required') || (state.errors[`branches.${index}.addressLine1`] = state.errors.addressLine1);
            delete state.errors.addressLine1;
            required(branch, 'city', 'City is required') || (state.errors[`branches.${index}.city`] = state.errors.city);
            delete state.errors.city;
            required(branch, 'state', 'State is required') || (state.errors[`branches.${index}.state`] = state.errors.state);
            delete state.errors.state;
            required(branch, 'country', 'Country is required') || (state.errors[`branches.${index}.country`] = state.errors.country);
            delete state.errors.country;
            required(branch, 'postalCode', 'Postal Code is required') || (state.errors[`branches.${index}.postalCode`] = state.errors.postalCode);
            delete state.errors.postalCode;
        });

        if (data.shieldLocationType === 'specific_branch' && !data.subscriptionBranchId) {
            addError('subscriptionBranchId', 'Please select the subscription branch office');
        }
    };

    const validateSecurity = (data) => {
        const password = String(getValue(data, 'security.password') || '');
        const confirm = String(getValue(data, 'security.confirmPassword') || '');
        if (password.length < 8) {
            addError('security.password', 'Password must be at least 8 characters long');
        } else if (!/[A-Z]/.test(password)) {
            addError('security.password', 'Password must contain at least one uppercase letter');
        } else if (!/[a-z]/.test(password)) {
            addError('security.password', 'Password must contain at least one lowercase letter');
        } else if (!/[0-9]/.test(password)) {
            addError('security.password', 'Password must contain at least one number');
        } else if (!/[^A-Za-z0-9]/.test(password)) {
            addError('security.password', 'Password must contain at least one special character');
        }

        if (!confirm) {
            addError('security.confirmPassword', 'Please confirm your password');
        } else if (password !== confirm) {
            addError('security.confirmPassword', 'Passwords do not match');
        }
    };

    const validateStep = () => {
        if (!state.persona) {
            return false;
        }

        state.errors = {};
        state.submitError = '';
        const data = activeData();

        if (state.persona === 'consultant') {
            if (state.step === 1) {
                required(data, 'fullName', 'Full name is required');
                validateEmail(data, 'email');
                validatePhone(data, 'mobileNumber');
                validateUrl(data, 'website', 'Invalid portfolio URL', true);
                validateUrl(data, 'linkedIn', 'Invalid LinkedIn URL', true);
                validateAddress(data, 'address');
                validateGstin(data, 'gstin');
                validateCv();
            } else if (state.step === 2) {
                if (!data.expertise || data.expertise.length === 0) {
                    addError('expertise', 'Please select at least one area of expertise');
                }
                if ((data.expertise || []).includes('Others')) {
                    required(data, 'customExpertise', 'Please specify your custom expertise');
                }
            } else if (state.step === 3) {
                validateSecurity(data);
            }
        }

        if (state.persona === 'company') {
            if (state.step === 1) {
                required(data, 'companyName', 'Company Name is required');
                required(data, 'registrationNumber', 'Registration Number is required');
                required(data, 'industry', 'Industry is required');
                validateUrl(data, 'website', 'Invalid website URL', false);
                validateEmail(data, 'contactEmail');
                validatePhone(data, 'contactNumber');
                validateAddress(data, 'registeredAddress');
                validateGstin(data, 'gstin');
            } else if (state.step === 2) {
                validateBranches(data);
            } else if (state.step === 3) {
                validateSecurity(data);
            }
        }

        if (state.persona === 'msp') {
            if (state.step === 1) {
                required(data, 'mspName', 'MSP name is required');
                required(data, 'businessName', 'Registered business name is required');
                validateUrl(data, 'website', 'Invalid website URL', false);
                validateEmail(data, 'contactEmail');
                validatePhone(data, 'contactNumber');
                validateAddress(data, 'registeredAddress');
                validateGstin(data, 'gstin');
            } else if (state.step === 2) {
                validateBranches(data);
            } else if (state.step === 3) {
                if (!data.services || data.services.length === 0) {
                    addError('services', 'Please select at least one service');
                }
                if ((data.services || []).includes('Others')) {
                    required(data, 'customServices', 'Please specify the custom service');
                }
            } else if (state.step === 4) {
                validateSecurity(data);
            }
        }

        return Object.keys(state.errors).length === 0;
    };

    const validateAll = () => {
        const current = state.step;
        const steps = getSteps();
        for (let index = 1; index < steps.length - 1; index += 1) {
            state.step = index;
            if (!validateStep()) {
                state.step = index;
                return false;
            }
        }
        state.step = current;
        state.errors = {};
        return true;
    };

    const makeBranch = (index) => ({
        id: Math.random().toString(36).slice(2, 9),
        branchName: `Branch Office ${index + 1}`,
        addressLine1: '',
        addressLine2: '',
        city: '',
        state: '',
        country: '',
        postalCode: '',
    });

    const normaliseBranchState = () => {
        const data = activeData();
        if (!data || !('branches' in data)) {
            return;
        }
        const branches = data.branches || [];
        const selectedExists = branches.some((branch) => branch.id === data.subscriptionBranchId);
        if (!selectedExists) {
            data.subscriptionBranchId = '';
        }
        if (branches.length === 0 || data.shieldLocationType !== 'specific_branch') {
            data.shieldLocationType = 'registered_office';
            data.billingAddressType = 'registered_office';
            data.subscriptionBranchId = '';
        }
        if (!data.subscriptionBranchId && data.billingAddressType === 'selected_branch') {
            data.billingAddressType = 'registered_office';
        }
    };

    const adjustBranchCount = (count) => {
        const data = activeData();
        if (!data || !('branches' in data)) {
            return;
        }
        const nextCount = Math.max(0, Math.min(25, Number.isFinite(count) ? count : 0));
        while (data.branches.length < nextCount) {
            data.branches.push(makeBranch(data.branches.length));
        }
        while (data.branches.length > nextCount) {
            data.branches.pop();
        }
        normaliseBranchState();
    };

    const handleFieldUpdate = (target) => {
        const path = target.dataset.field;
        if (!path || !state.persona) {
            return false;
        }
        const data = activeData();
        const value = target.type === 'radio' ? target.value : target.value;
        setValue(data, path, value);
        delete state.errors[path];

        if (path === 'gstin') {
            setValue(data, path, value.toUpperCase());
        }

        if (path === 'shieldLocationType' || path === 'subscriptionBranchId' || path === 'billingAddressType' || path.startsWith('branches.')) {
            normaliseBranchState();
            return true;
        }

        return path.startsWith('security.');
    };

    root.addEventListener('input', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLInputElement)) {
            return;
        }

        if (target.dataset.branchCount !== undefined) {
            adjustBranchCount(parseInt(target.value, 10));
            render();
            return;
        }

        const hadError = !!state.errors[target.dataset.field || ''];
        const shouldRender = handleFieldUpdate(target);
        if (shouldRender || hadError) {
            render();
        }
    });

    root.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLInputElement) && !(target instanceof HTMLSelectElement)) {
            return;
        }

        if (target.dataset.arrayField) {
            const data = activeData();
            const path = target.dataset.arrayField;
            const selected = getValue(data, path) || [];
            if (target.checked) {
                selected.push(target.value);
            } else {
                const index = selected.indexOf(target.value);
                if (index >= 0) {
                    selected.splice(index, 1);
                }
            }
            setValue(data, path, selected);
            delete state.errors[path];
            render();
            return;
        }

        if (target.dataset.fileField) {
            state.cvFile = target.files && target.files[0] ? target.files[0] : null;
            if (state.cvFile) {
                setValue(activeData(), 'cvName', state.cvFile.name);
            }
            delete state.errors[target.dataset.fileField];
            render();
            return;
        }

        if (target.dataset.field) {
            handleFieldUpdate(target);
            render();
        }
    });

    root.addEventListener('click', async (event) => {
        const target = event.target.closest('[data-select-persona], [data-action]');
        if (!target) {
            return;
        }

        if (target.dataset.selectPersona) {
            state.persona = target.dataset.selectPersona;
            state.step = 1;
            state.errors = {};
            render(true);
            return;
        }

        const action = target.dataset.action;

        if (action === 'back') {
            state.step = Math.max(0, state.step - 1);
            state.errors = {};
            render(true);
            return;
        }

        if (action === 'next') {
            if (validateStep()) {
                state.step += 1;
                render(true);
            } else {
                render();
            }
            return;
        }

        if (action === 'edit-step') {
            state.step = parseInt(target.dataset.step || '0', 10);
            state.errors = {};
            render(true);
            return;
        }

        if (action === 'add-branch') {
            const data = activeData();
            data.branches.push(makeBranch(data.branches.length));
            render();
            return;
        }

        if (action === 'remove-branch') {
            const data = activeData();
            data.branches.splice(parseInt(target.dataset.index || '0', 10), 1);
            normaliseBranchState();
            render();
            return;
        }

        if (action === 'toggle-password') {
            const path = target.dataset.path;
            state.visiblePasswords[path] = !state.visiblePasswords[path];
            render();
            return;
        }

        if (action === 'submit-registration') {
            await submitRegistration();
            return;
        }

        if (action === 'reset') {
            if (state.submitted) {
                window.location.href = window.location.pathname;
                return;
            }
            state.persona = null;
            state.step = 0;
            state.forms = defaultForms();
            state.errors = {};
            state.submitting = false;
            state.submitError = '';
            state.submitted = null;
            render(true);
            return;
        }

        if (action === 'login') {
            window.location.href = root.dataset.loginUrl || 'login';
        }
    });

    const getCsrfToken = async () => {
        if (state.csrfToken) {
            return state.csrfToken;
        }

        const response = await fetch(csrfUrl, {
            headers: {
                'Accept': 'application/json',
            },
        });
        const result = await response.json();
        state.csrfToken = result.token || '';

        return state.csrfToken;
    };

    const submitRegistration = async () => {
        if (!validateAll()) {
            render(true);
            return;
        }

        state.submitting = true;
        state.submitError = '';
        render();

        try {
            await new Promise((resolve) => setTimeout(resolve, 700));
            const csrfToken = await getCsrfToken();
            const headers = {
                'Accept': 'application/json',
                'X-CSRF-Token': csrfToken,
            };
            const personaForApi = {
                msp: 'MSP',
                company: 'COMPANY',
                consultant: 'IT_CONSULTANT',
            }[state.persona] || state.persona;
            const payload = activeData();
            const selectedCvFile = state.cvFile instanceof File ? state.cvFile : null;
            let body;

            if (selectedCvFile) {
                body = new FormData();
                body.append('persona', personaForApi);
                body.append('payload', JSON.stringify(payload));
                body.append('cv', selectedCvFile, selectedCvFile.name);
            } else {
                headers['Content-Type'] = 'application/json';
                body = JSON.stringify({
                    persona: personaForApi,
                    payload,
                });
            }

            const response = await fetch(submitUrl, {
                method: 'POST',
                headers,
                body,
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                state.errors = result.errors || {};
                state.submitError = result.message || 'Registration could not be submitted.';
                state.submitting = false;
                render();
                return;
            }

            state.submitted = result;
            state.submitting = false;
            renderSuccess();
        } catch (error) {
            state.submitting = false;
            state.submitError = 'Unable to reach the PHP registration endpoint.';
            render();
        }
    };

    if (formEl) {
        formEl.addEventListener('submit', async (event) => {
            event.preventDefault();
            await submitRegistration();
        });
    }

    const renderSuccess = () => {
        const data = state.submitted.data || {};
        const isCorp = state.persona === 'msp' || state.persona === 'company';
        const email = isCorp ? data.contactEmail : data.email;
        const entity = state.persona === 'msp'
            ? data.mspName
            : state.persona === 'company'
                ? data.companyName
                : data.fullName;

        root.className = 'success-screen';
        root.innerHTML = `
            <section class="success-card">
                <div class="success-icon">${icon('checkCircle', 'lg')}</div>
                <h1>Onboarding Successful!</h1>
                <p>Your Kenexoft SHIELD profile has been provisioned and cataloged on our secure systems.</p>

                <div class="provision-table">
                    <div><span>Persona Profile:</span><strong>${escapeHtml(state.persona)}</strong></div>
                    <div><span>Account Holder:</span><strong>${escapeHtml(entity || 'N/A')}</strong></div>
                    <div><span>Console Username:</span><strong>${escapeHtml(email || 'N/A')}</strong></div>
                    <div><span>Reference Number:</span><strong>${escapeHtml(state.submitted.referenceId)}</strong></div>
                    <div><span>Node Provisioning:</span><strong>Completed (Live)</strong></div>
                </div>

                <div class="next-steps">
                    <strong>Next steps for ${(state.persona || '').toUpperCase()}:</strong>
                    Our provisioning agent is setting up your SHIELD control console. A verification token and node access keys will arrive at <strong>${escapeHtml(email || 'your email')}</strong> within 15 minutes.
                </div>

                <div class="success-actions">
                    <button type="button" class="btn btn-green" data-action="login">Go To Login</button>
                    <button type="button" class="btn btn-dark" data-action="reset">Register New Node</button>
                </div>
            </section>
        `;
    };

    render();
})();
