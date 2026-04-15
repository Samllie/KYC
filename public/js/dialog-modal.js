(function () {
    const ids = {
        modal: 'dialogModal',
        title: 'dialogModalTitle',
        message: 'dialogModalMessage',
        promptWrap: 'dialogModalPromptWrap',
        promptLabel: 'dialogModalPromptLabel',
        promptInput: 'dialogModalPromptInput',
        cancelBtn: 'dialogModalCancelBtn',
        closeBtn: 'dialogModalCloseBtn',
        confirmBtn: 'dialogModalConfirmBtn',
    };

    const state = {
        modal: null,
        titleEl: null,
        messageEl: null,
        promptWrapEl: null,
        promptLabelEl: null,
        promptInputEl: null,
        cancelBtnEl: null,
        closeBtnEl: null,
        confirmBtnEl: null,
        resolver: null,
        mode: 'confirm',
        previousBodyOverflow: '',
        previousFocus: null,
    };

    function normalizeOptions(messageOrOptions, maybeOptions) {
        if (typeof messageOrOptions === 'string') {
            return {
                message: messageOrOptions,
                ...(maybeOptions || {}),
            };
        }

        return {
            ...(messageOrOptions || {}),
        };
    }

    function getConfirmVariantClass(variant) {
        return variant === 'danger' ? 'btn-delete-confirm' : 'btn-save';
    }

    function isDialogOpen() {
        return Boolean(state.modal && state.modal.style.display === 'block');
    }

    function hideDialog() {
        if (!state.modal) {
            return;
        }

        state.modal.style.display = 'none';
        state.modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = state.previousBodyOverflow;

        if (state.promptInputEl) {
            state.promptInputEl.value = '';
        }

        if (state.promptWrapEl) {
            state.promptWrapEl.hidden = true;
        }

        if (state.cancelBtnEl) {
            state.cancelBtnEl.hidden = false;
        }

        state.previousBodyOverflow = '';

        if (state.previousFocus && typeof state.previousFocus.focus === 'function') {
            try {
                state.previousFocus.focus();
            } catch (error) {
                void error;
            }
        }

        state.previousFocus = null;
    }

    function finishDialog(result) {
        const resolver = state.resolver;
        state.resolver = null;
        hideDialog();

        if (resolver) {
            resolver(result);
        }
    }

    function ensureDialog() {
        if (state.modal) {
            return state;
        }

        if (!document.body) {
            document.addEventListener('DOMContentLoaded', ensureDialog, { once: true });
            return state;
        }

        const modal = document.createElement('div');
        modal.id = ids.modal;
        modal.className = 'modal';
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = `
            <div class="modal-content delete-modal-content" role="dialog" aria-modal="true" aria-labelledby="${ids.title}" aria-describedby="${ids.message}">
                <div class="modal-header">
                    <h2 id="${ids.title}">Confirm</h2>
                    <button id="${ids.closeBtn}" type="button" class="modal-close" title="Close"><i class="bi bi-x"></i></button>
                </div>
                <div class="modal-body delete-modal-body">
                    <p id="${ids.message}"></p>
                    <div id="${ids.promptWrap}" hidden>
                        <label id="${ids.promptLabel}" class="form-label" for="${ids.promptInput}">Note</label>
                        <textarea id="${ids.promptInput}" class="form-control" rows="4" placeholder=""></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="${ids.cancelBtn}" class="btn-cancel" type="button">Cancel</button>
                    <button id="${ids.confirmBtn}" class="btn-save" type="button">Confirm</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        state.modal = modal;
        state.titleEl = modal.querySelector(`#${ids.title}`);
        state.messageEl = modal.querySelector(`#${ids.message}`);
        state.promptWrapEl = modal.querySelector(`#${ids.promptWrap}`);
        state.promptLabelEl = modal.querySelector(`#${ids.promptLabel}`);
        state.promptInputEl = modal.querySelector(`#${ids.promptInput}`);
        state.cancelBtnEl = modal.querySelector(`#${ids.cancelBtn}`);
        state.closeBtnEl = modal.querySelector(`#${ids.closeBtn}`);
        state.confirmBtnEl = modal.querySelector(`#${ids.confirmBtn}`);

        const handleClose = () => {
            if (!state.resolver) {
                hideDialog();
                return;
            }

            finishDialog(state.mode === 'prompt' ? null : false);
        };

        const handleConfirm = () => {
            if (!state.resolver) {
                hideDialog();
                return;
            }

            if (state.mode === 'prompt') {
                finishDialog(state.promptInputEl ? state.promptInputEl.value : '');
                return;
            }

            finishDialog(true);
        };

        const handleBackdropClick = event => {
            if (event.target === modal) {
                handleClose();
            }
        };

        const handleKeyDown = event => {
            if (!isDialogOpen()) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                handleClose();
                return;
            }

            if (state.mode === 'prompt' && event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
                event.preventDefault();
                handleConfirm();
            }
        };

        if (state.closeBtnEl) {
            state.closeBtnEl.addEventListener('click', handleClose);
        }

        if (state.cancelBtnEl) {
            state.cancelBtnEl.addEventListener('click', handleClose);
        }

        if (state.confirmBtnEl) {
            state.confirmBtnEl.addEventListener('click', handleConfirm);
        }

        modal.addEventListener('click', handleBackdropClick);
        document.addEventListener('keydown', handleKeyDown);

        return state;
    }

    function openDialog(mode, messageOrOptions, maybeOptions) {
        const options = normalizeOptions(messageOrOptions, maybeOptions);
        ensureDialog();

        if (!state.modal) {
            return Promise.resolve(mode === 'prompt' ? null : false);
        }

        if (state.resolver) {
            finishDialog(mode === 'prompt' ? null : false);
        }

        const title = String(options.title || (mode === 'prompt' ? 'Enter Value' : 'Confirm Action'));
        const message = String(options.message || '');
        const confirmText = String(options.confirmText || (mode === 'prompt' ? 'Continue' : 'Confirm'));
        const cancelText = String(options.cancelText || 'Cancel');
        const variant = String(options.variant || (mode === 'confirm' ? 'danger' : 'success'));
        const promptLabel = String(options.promptLabel || 'Note');
        const promptPlaceholder = String(options.promptPlaceholder || '');
        const defaultValue = String(options.defaultValue || '');

        state.mode = mode;
        state.previousFocus = document.activeElement && document.activeElement !== document.body ? document.activeElement : null;
        state.previousBodyOverflow = document.body.style.overflow;

        if (state.titleEl) {
            state.titleEl.textContent = title;
        }

        if (state.messageEl) {
            state.messageEl.textContent = message;
        }

        if (state.promptWrapEl) {
            state.promptWrapEl.hidden = mode !== 'prompt';
        }

        if (state.promptLabelEl) {
            state.promptLabelEl.textContent = promptLabel;
        }

        if (state.promptInputEl) {
            state.promptInputEl.value = defaultValue;
            state.promptInputEl.placeholder = promptPlaceholder;
        }

        if (state.cancelBtnEl) {
            state.cancelBtnEl.hidden = mode === 'alert';
            state.cancelBtnEl.textContent = cancelText;
        }

        if (state.closeBtnEl) {
            state.closeBtnEl.title = cancelText;
        }

        if (state.confirmBtnEl) {
            state.confirmBtnEl.textContent = confirmText;
            state.confirmBtnEl.classList.remove('btn-delete-confirm', 'btn-save');
            state.confirmBtnEl.classList.add(getConfirmVariantClass(variant));
        }

        state.modal.style.display = 'block';
        state.modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        return new Promise(resolve => {
            state.resolver = resolve;

            window.requestAnimationFrame(() => {
                if (state.mode === 'prompt' && state.promptInputEl) {
                    state.promptInputEl.focus();
                    state.promptInputEl.select();
                    return;
                }

                if (state.confirmBtnEl) {
                    state.confirmBtnEl.focus();
                }
            });
        });
    }

    window.showConfirmModal = function showConfirmModal(messageOrOptions, maybeOptions) {
        return openDialog('confirm', messageOrOptions, maybeOptions);
    };

    window.showPromptModal = function showPromptModal(messageOrOptions, maybeOptions) {
        return openDialog('prompt', messageOrOptions, maybeOptions);
    };

    window.showAlertModal = function showAlertModal(messageOrOptions, maybeOptions) {
        return openDialog('alert', messageOrOptions, maybeOptions);
    };
})();