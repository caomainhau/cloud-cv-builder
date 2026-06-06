(() => {
    'use strict';

    const form = document.getElementById('resume-form');
    const sectionsRoot = document.getElementById('dynamic-sections');
    const sectionsJson = document.getElementById('sections-json');
    const preview = document.getElementById('live-preview');
    const templateSelect = document.getElementById('template-select');
    const initialNode = document.getElementById('initial-sections');

    if (!form || !sectionsRoot || !sectionsJson || !preview || !initialNode) return;

    const definitions = {
        experiences: {
            title: 'Kinh nghiệm', add: '+ Thêm kinh nghiệm',
            fields: [
                ['role', 'Vị trí', 'text', 'Ví dụ: SOC Intern'],
                ['company', 'Công ty hoặc tổ chức', 'text', 'Tên đơn vị'],
                ['start', 'Bắt đầu', 'text', '01/2026'],
                ['end', 'Kết thúc', 'text', 'Hiện tại'],
                ['description', 'Mô tả', 'textarea', 'Nêu nhiệm vụ và kết quả đạt được.'],
            ],
        },
        projects: {
            title: 'Dự án', add: '+ Thêm dự án',
            fields: [
                ['name', 'Tên dự án', 'text', 'Windows DFIR Attack Chain Investigation'],
                ['technologies', 'Công nghệ', 'text', 'Sysmon, Windows Event Log, MITRE ATT&CK'],
                ['description', 'Mô tả', 'textarea', 'Nêu vấn đề, cách triển khai và kết quả.'],
                ['url', 'Liên kết', 'url', 'https://github.com/...'],
            ],
        },
        educations: {
            title: 'Học vấn', add: '+ Thêm học vấn',
            fields: [
                ['school', 'Trường', 'text', 'Tên trường'],
                ['degree', 'Ngành hoặc chương trình', 'text', 'Công nghệ thông tin'],
                ['start', 'Bắt đầu', 'text', '2022'],
                ['end', 'Kết thúc', 'text', '2026'],
                ['description', 'Thông tin thêm', 'textarea', 'GPA hoặc nội dung nổi bật nếu cần.'],
            ],
        },
        skills: {
            title: 'Kỹ năng', add: '+ Thêm kỹ năng',
            fields: [['name', 'Kỹ năng hoặc nhóm kỹ năng', 'text', 'Wireshark, Sysmon, Python']],
        },
        certificates: {
            title: 'Chứng chỉ', add: '+ Thêm chứng chỉ',
            fields: [
                ['name', 'Tên chứng chỉ', 'text', 'Tên chứng chỉ'],
                ['issuer', 'Đơn vị cấp', 'text', 'Đơn vị cấp'],
                ['year', 'Năm', 'text', '2026'],
            ],
        },
        languages: {
            title: 'Ngoại ngữ', add: '+ Thêm ngoại ngữ',
            fields: [
                ['name', 'Ngôn ngữ', 'text', 'Tiếng Anh'],
                ['level', 'Trình độ', 'text', 'Giao tiếp cơ bản'],
            ],
        },
        links: {
            title: 'Liên kết', add: '+ Thêm liên kết',
            fields: [
                ['label', 'Nhãn', 'text', 'GitHub'],
                ['url', 'Đường dẫn', 'url', 'https://github.com/...'],
            ],
        },
    };

    const blankSections = Object.fromEntries(Object.keys(definitions).map((key) => [key, []]));
    let sections;
    try {
        sections = { ...blankSections, ...JSON.parse(initialNode.textContent || '{}') };
    } catch {
        sections = structuredClone(blankSections);
    }

    for (const key of Object.keys(definitions)) {
        if (!Array.isArray(sections[key])) sections[key] = [];
    }

    const esc = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const nl2br = (value) => esc(value).replace(/\n/g, '<br>');

    const fieldValue = (name) => {
        const field = form.elements.namedItem(name);
        return field ? String(field.value ?? '').trim() : '';
    };

    const cleanUrl = (value) => {
        let url = String(value ?? '').trim();
        if (!url) return '';
        if (!/^https?:\/\//i.test(url)) url = `https://${url}`;
        try {
            const parsed = new URL(url);
            return ['http:', 'https:'].includes(parsed.protocol) ? parsed.href : '';
        } catch {
            return '';
        }
    };

    const syncJson = () => {
        sectionsJson.value = JSON.stringify(sections);
    };

    const renderInput = (sectionKey, itemIndex, [field, label, type, placeholder], value) => {
        const attrs = `data-section="${esc(sectionKey)}" data-index="${itemIndex}" data-field="${esc(field)}" placeholder="${esc(placeholder)}"`;
        if (type === 'textarea') {
            return `<label>${esc(label)}<textarea rows="3" ${attrs}>${esc(value)}</textarea></label>`;
        }
        return `<label>${esc(label)}<input type="${esc(type)}" value="${esc(value)}" ${attrs}></label>`;
    };

    const renderEditors = () => {
        sectionsRoot.innerHTML = Object.entries(definitions).map(([sectionKey, definition]) => {
            const items = sections[sectionKey];
            const itemHtml = items.map((item, itemIndex) => `
                <div class="dynamic-item">
                    <button class="remove-item" type="button" data-remove="${esc(sectionKey)}" data-index="${itemIndex}">Xóa</button>
                    ${definition.fields.map((field) => renderInput(sectionKey, itemIndex, field, item[field[0]] ?? '')).join('')}
                </div>
            `).join('');

            return `
                <div class="form-card">
                    <div class="form-card-header">
                        <h2>${esc(definition.title)}</h2>
                        <button class="link-button" type="button" data-add="${esc(sectionKey)}">${esc(definition.add)}</button>
                    </div>
                    ${itemHtml || '<p class="muted small">Chưa có nội dung. Bạn có thể bỏ qua mục này.</p>'}
                </div>
            `;
        }).join('');
        syncJson();
    };

    const listIsNotEmpty = (key) => sections[key].some((item) => Object.values(item).some((value) => String(value ?? '').trim()));

    const entryHeader = (main, side) => `<div class="cv-entry-heading"><strong>${esc(main)}</strong>${side ? `<span>${esc(side)}</span>` : ''}</div>`;

    const renderPreview = () => {
        syncJson();
        const fullName = fieldValue('full_name') || 'HỌ VÀ TÊN';
        const jobTitle = fieldValue('job_title');
        const summary = fieldValue('summary');
        const contact = [fieldValue('email'), fieldValue('phone'), fieldValue('location')].filter(Boolean).join(' · ');
        const template = templateSelect ? templateSelect.value : 'ats-simple';

        preview.className = `cv-document ${template}`;
        let html = `
            <header class="cv-header">
                <h1>${esc(fullName)}</h1>
                ${jobTitle ? `<p class="cv-job-title">${esc(jobTitle)}</p>` : ''}
                ${contact ? `<p class="cv-contact">${esc(contact)}</p>` : ''}
            </header>
        `;

        if (summary) html += `<section class="cv-section"><h2>Giới thiệu</h2><p>${nl2br(summary)}</p></section>`;

        if (listIsNotEmpty('experiences')) {
            html += `<section class="cv-section"><h2>Kinh nghiệm</h2>${sections.experiences.map((item) => `
                <div class="cv-entry">
                    ${entryHeader(item.role, [item.start, item.end].filter(Boolean).join(' – '))}
                    ${item.company ? `<div class="cv-entry-subtitle">${esc(item.company)}</div>` : ''}
                    ${item.description ? `<p>${nl2br(item.description)}</p>` : ''}
                </div>`).join('')}</section>`;
        }

        if (listIsNotEmpty('projects')) {
            html += `<section class="cv-section"><h2>Dự án</h2>${sections.projects.map((item) => {
                const url = cleanUrl(item.url);
                return `<div class="cv-entry">
                    <div class="cv-entry-heading"><strong>${esc(item.name)}</strong>${url ? `<a href="${esc(url)}" target="_blank" rel="noopener">Liên kết</a>` : ''}</div>
                    ${item.technologies ? `<div class="cv-entry-subtitle">${esc(item.technologies)}</div>` : ''}
                    ${item.description ? `<p>${nl2br(item.description)}</p>` : ''}
                </div>`;
            }).join('')}</section>`;
        }

        if (listIsNotEmpty('educations')) {
            html += `<section class="cv-section"><h2>Học vấn</h2>${sections.educations.map((item) => `
                <div class="cv-entry">
                    ${entryHeader(item.school, [item.start, item.end].filter(Boolean).join(' – '))}
                    ${item.degree ? `<div class="cv-entry-subtitle">${esc(item.degree)}</div>` : ''}
                    ${item.description ? `<p>${nl2br(item.description)}</p>` : ''}
                </div>`).join('')}</section>`;
        }

        if (listIsNotEmpty('skills')) {
            html += `<section class="cv-section"><h2>Kỹ năng</h2><p class="cv-tags">${sections.skills.filter((item) => item.name).map((item) => `<span>${esc(item.name)}</span>`).join('')}</p></section>`;
        }

        if (listIsNotEmpty('certificates')) {
            html += `<section class="cv-section"><h2>Chứng chỉ</h2>${sections.certificates.map((item) => `
                <div class="cv-entry compact">
                    ${entryHeader(item.name, item.year)}
                    ${item.issuer ? `<div class="cv-entry-subtitle">${esc(item.issuer)}</div>` : ''}
                </div>`).join('')}</section>`;
        }

        if (listIsNotEmpty('languages')) {
            html += `<section class="cv-section"><h2>Ngoại ngữ</h2><p class="cv-list-inline">${sections.languages.filter((item) => item.name).map((item) => `<span><strong>${esc(item.name)}</strong>${item.level ? `: ${esc(item.level)}` : ''}</span>`).join('')}</p></section>`;
        }

        if (listIsNotEmpty('links')) {
            html += `<section class="cv-section"><h2>Liên kết</h2><p class="cv-list-inline">${sections.links.map((item) => {
                const url = cleanUrl(item.url);
                return url ? `<a href="${esc(url)}" target="_blank" rel="noopener">${esc(item.label || url)}</a>` : '';
            }).join('')}</p></section>`;
        }

        preview.innerHTML = html;
    };

    sectionsRoot.addEventListener('click', (event) => {
        const addButton = event.target.closest('[data-add]');
        if (addButton) {
            const key = addButton.dataset.add;
            if (!definitions[key]) return;
            const item = Object.fromEntries(definitions[key].fields.map(([field]) => [field, '']));
            sections[key].push(item);
            renderEditors();
            renderPreview();
            return;
        }

        const removeButton = event.target.closest('[data-remove]');
        if (removeButton) {
            const key = removeButton.dataset.remove;
            const index = Number(removeButton.dataset.index);
            if (!definitions[key] || Number.isNaN(index)) return;
            sections[key].splice(index, 1);
            renderEditors();
            renderPreview();
        }
    });

    sectionsRoot.addEventListener('input', (event) => {
        const element = event.target.closest('[data-section][data-index][data-field]');
        if (!element) return;
        const key = element.dataset.section;
        const index = Number(element.dataset.index);
        const field = element.dataset.field;
        if (!sections[key] || !sections[key][index]) return;
        sections[key][index][field] = element.value;
        renderPreview();
    });

    form.addEventListener('input', (event) => {
        if (!event.target.closest('[data-section]')) renderPreview();
    });
    form.addEventListener('change', renderPreview);
    form.addEventListener('submit', syncJson);

    renderEditors();
    renderPreview();
})();
