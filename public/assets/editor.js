(() => {
    'use strict';

    const form = document.getElementById('resume-form');
    const sectionsRoot = document.getElementById('dynamic-sections');
    const sectionsJson = document.getElementById('sections-json');
    const preview = document.getElementById('live-preview');
    const templateSelect = document.getElementById('template-select');
    const initialNode = document.getElementById('initial-sections');
    const autosaveStatus = document.getElementById('autosave-status');
    const previewLink = document.getElementById('preview-link');

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
    let dirty = false;
    let saving = false;
    let autosaveTimer = null;
    let manualSubmitting = false;
    let dragState = null;

    try {
        sections = { ...blankSections, ...JSON.parse(initialNode.textContent || '{}') };
    } catch {
        sections = structuredClone(blankSections);
    }

    for (const key of Object.keys(definitions)) {
        if (!Array.isArray(sections[key])) sections[key] = [];
        sections[key] = sections[key].map((item) => ({ ...item, _visible: item?._visible !== false }));
    }

    const esc = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const nl2br = (value) => esc(value).replace(/\n/g, '<br>');
    const visibleItems = (key) => sections[key].filter((item) => item._visible !== false);

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

    const beforeUnloadHandler = (event) => {
        if (!dirty && !saving) return;
        event.preventDefault();
        event.returnValue = true;
    };

    const setStatus = (message, state = 'idle') => {
        if (!autosaveStatus) return;
        autosaveStatus.textContent = message;
        autosaveStatus.dataset.state = state;
    };

    const updateBeforeUnload = () => {
        window.removeEventListener('beforeunload', beforeUnloadHandler);
        if (!manualSubmitting && (dirty || saving)) {
            window.addEventListener('beforeunload', beforeUnloadHandler);
        }
    };

    const scheduleAutosave = () => {
        window.clearTimeout(autosaveTimer);
        autosaveTimer = window.setTimeout(runAutosave, 1100);
    };

    const markDirty = () => {
        dirty = true;
        setStatus('Có thay đổi chưa lưu', 'dirty');
        updateBeforeUnload();
        scheduleAutosave();
    };

    const runAutosave = async () => {
        if (!dirty || saving || manualSubmitting) return;
        dirty = false;
        saving = true;
        syncJson();
        setStatus('Đang lưu tự động…', 'saving');
        updateBeforeUnload();

        try {
            const response = await fetch('/resume/autosave', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const payload = await response.json();
            if (!payload.ok) throw new Error(payload.message || 'Autosave failed');
            setStatus(`Đã lưu tự động · ${new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })}`, 'saved');
        } catch {
            dirty = true;
            setStatus('Chưa thể lưu tự động · bấm Lưu ngay', 'error');
        } finally {
            saving = false;
            updateBeforeUnload();
            if (dirty) scheduleAutosave();
        }
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
                <div class="dynamic-item ${item._visible === false ? 'is-hidden' : ''}" data-section-item="${esc(sectionKey)}" data-index="${itemIndex}">
                    <div class="dynamic-item-toolbar">
                        <button class="drag-handle" type="button" draggable="true" data-drag-handle="${esc(sectionKey)}" data-index="${itemIndex}" title="Kéo để thay đổi thứ tự" aria-label="Kéo để thay đổi thứ tự">⋮⋮</button>
                        <button class="item-tool" type="button" data-move="${esc(sectionKey)}" data-index="${itemIndex}" data-direction="-1" title="Đưa lên">↑</button>
                        <button class="item-tool" type="button" data-move="${esc(sectionKey)}" data-index="${itemIndex}" data-direction="1" title="Đưa xuống">↓</button>
                        <button class="item-tool" type="button" data-toggle-visible="${esc(sectionKey)}" data-index="${itemIndex}">${item._visible === false ? 'Hiện' : 'Ẩn'}</button>
                        <button class="remove-item" type="button" data-remove="${esc(sectionKey)}" data-index="${itemIndex}">Xóa</button>
                    </div>
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

    const listIsNotEmpty = (key) => visibleItems(key).some((item) => Object.entries(item).some(([field, value]) => field !== '_visible' && String(value ?? '').trim()));
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
            html += `<section class="cv-section"><h2>Kinh nghiệm</h2>${visibleItems('experiences').map((item) => `
                <div class="cv-entry">
                    ${entryHeader(item.role, [item.start, item.end].filter(Boolean).join(' – '))}
                    ${item.company ? `<div class="cv-entry-subtitle">${esc(item.company)}</div>` : ''}
                    ${item.description ? `<p>${nl2br(item.description)}</p>` : ''}
                </div>`).join('')}</section>`;
        }

        if (listIsNotEmpty('projects')) {
            html += `<section class="cv-section"><h2>Dự án</h2>${visibleItems('projects').map((item) => {
                const url = cleanUrl(item.url);
                return `<div class="cv-entry">
                    <div class="cv-entry-heading"><strong>${esc(item.name)}</strong>${url ? `<a href="${esc(url)}" target="_blank" rel="noopener">Liên kết</a>` : ''}</div>
                    ${item.technologies ? `<div class="cv-entry-subtitle">${esc(item.technologies)}</div>` : ''}
                    ${item.description ? `<p>${nl2br(item.description)}</p>` : ''}
                </div>`;
            }).join('')}</section>`;
        }

        if (listIsNotEmpty('educations')) {
            html += `<section class="cv-section"><h2>Học vấn</h2>${visibleItems('educations').map((item) => `
                <div class="cv-entry">
                    ${entryHeader(item.school, [item.start, item.end].filter(Boolean).join(' – '))}
                    ${item.degree ? `<div class="cv-entry-subtitle">${esc(item.degree)}</div>` : ''}
                    ${item.description ? `<p>${nl2br(item.description)}</p>` : ''}
                </div>`).join('')}</section>`;
        }

        if (listIsNotEmpty('skills')) {
            html += `<section class="cv-section"><h2>Kỹ năng</h2><p class="cv-tags">${visibleItems('skills').filter((item) => item.name).map((item) => `<span>${esc(item.name)}</span>`).join('')}</p></section>`;
        }

        if (listIsNotEmpty('certificates')) {
            html += `<section class="cv-section"><h2>Chứng chỉ</h2>${visibleItems('certificates').map((item) => `
                <div class="cv-entry compact">
                    ${entryHeader(item.name, item.year)}
                    ${item.issuer ? `<div class="cv-entry-subtitle">${esc(item.issuer)}</div>` : ''}
                </div>`).join('')}</section>`;
        }

        if (listIsNotEmpty('languages')) {
            html += `<section class="cv-section"><h2>Ngoại ngữ</h2><p class="cv-list-inline">${visibleItems('languages').filter((item) => item.name).map((item) => `<span><strong>${esc(item.name)}</strong>${item.level ? `: ${esc(item.level)}` : ''}</span>`).join('')}</p></section>`;
        }

        if (listIsNotEmpty('links')) {
            html += `<section class="cv-section"><h2>Liên kết</h2><p class="cv-list-inline">${visibleItems('links').map((item) => {
                const url = cleanUrl(item.url);
                return url ? `<a href="${esc(url)}" target="_blank" rel="noopener">${esc(item.label || url)}</a>` : '';
            }).join('')}</p></section>`;
        }

        preview.innerHTML = html;
    };

    const moveItem = (key, fromIndex, toIndex) => {
        if (!definitions[key] || fromIndex === toIndex || fromIndex < 0 || toIndex < 0 || toIndex >= sections[key].length) return;
        const [item] = sections[key].splice(fromIndex, 1);
        sections[key].splice(toIndex, 0, item);
        renderEditors();
        renderPreview();
        markDirty();
    };

    sectionsRoot.addEventListener('click', (event) => {
        const addButton = event.target.closest('[data-add]');
        if (addButton) {
            const key = addButton.dataset.add;
            if (!definitions[key]) return;
            const item = Object.fromEntries(definitions[key].fields.map(([field]) => [field, '']));
            item._visible = true;
            sections[key].push(item);
            renderEditors();
            renderPreview();
            markDirty();
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
            markDirty();
            return;
        }

        const visibilityButton = event.target.closest('[data-toggle-visible]');
        if (visibilityButton) {
            const key = visibilityButton.dataset.toggleVisible;
            const index = Number(visibilityButton.dataset.index);
            if (!sections[key]?.[index]) return;
            sections[key][index]._visible = sections[key][index]._visible === false;
            renderEditors();
            renderPreview();
            markDirty();
            return;
        }

        const moveButton = event.target.closest('[data-move]');
        if (moveButton) {
            const key = moveButton.dataset.move;
            const index = Number(moveButton.dataset.index);
            const direction = Number(moveButton.dataset.direction);
            moveItem(key, index, index + direction);
        }
    });

    sectionsRoot.addEventListener('input', (event) => {
        const element = event.target.closest('[data-section][data-index][data-field]');
        if (!element) return;
        const key = element.dataset.section;
        const index = Number(element.dataset.index);
        const field = element.dataset.field;
        if (!sections[key]?.[index]) return;
        sections[key][index][field] = element.value;
        renderPreview();
        markDirty();
    });

    sectionsRoot.addEventListener('dragstart', (event) => {
        const handle = event.target.closest('[data-drag-handle]');
        if (!handle) return;
        dragState = { key: handle.dataset.dragHandle, index: Number(handle.dataset.index) };
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', `${dragState.key}:${dragState.index}`);
        handle.closest('.dynamic-item')?.classList.add('is-dragging');
    });

    sectionsRoot.addEventListener('dragover', (event) => {
        const target = event.target.closest('[data-section-item]');
        if (!target || !dragState || target.dataset.sectionItem !== dragState.key) return;
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
    });

    sectionsRoot.addEventListener('drop', (event) => {
        const target = event.target.closest('[data-section-item]');
        if (!target || !dragState || target.dataset.sectionItem !== dragState.key) return;
        event.preventDefault();
        const targetIndex = Number(target.dataset.index);
        moveItem(dragState.key, dragState.index, targetIndex);
        dragState = null;
    });

    sectionsRoot.addEventListener('dragend', () => {
        dragState = null;
        document.querySelectorAll('.is-dragging').forEach((item) => item.classList.remove('is-dragging'));
    });

    form.addEventListener('input', (event) => {
        if (!event.target.closest('[data-section]')) {
            renderPreview();
            markDirty();
        }
    });

    form.addEventListener('change', (event) => {
        if (!event.target.closest('[data-section]')) {
            renderPreview();
            markDirty();
        }
    });

    const waitForSaved = async () => {
        if (dirty && !saving) await runAutosave();
        while (saving) {
            await new Promise((resolve) => window.setTimeout(resolve, 100));
        }
        if (dirty) await runAutosave();
        return !dirty && !saving;
    };

    previewLink?.addEventListener('click', async (event) => {
        if (!dirty && !saving) return;
        event.preventDefault();
        const opened = await waitForSaved();
        if (opened) {
            window.open(previewLink.href, '_blank', 'noopener');
        } else {
            setStatus('Chưa thể mở bản in · hãy bấm Lưu ngay', 'error');
        }
    });

    form.addEventListener('submit', (event) => {
        if (saving) {
            event.preventDefault();
            setStatus('Đang hoàn tất lưu tự động…', 'saving');
            window.setTimeout(() => form.requestSubmit(), 180);
            return;
        }
        manualSubmitting = true;
        window.clearTimeout(autosaveTimer);
        syncJson();
        window.removeEventListener('beforeunload', beforeUnloadHandler);
        setStatus('Đang lưu…', 'saving');
    });

    renderEditors();
    renderPreview();
})();
