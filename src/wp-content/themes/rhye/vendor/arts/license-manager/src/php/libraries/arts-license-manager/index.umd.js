/*!
 * Arts License Manager v1.0.2
 * https://artemsemkin.com
 * © 2025 Artem Semkin
 * License: MIT
 */
(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory();
	else if(typeof define === 'function' && define.amd)
		define([], factory);
	else if(typeof exports === 'object')
		exports["ArtsNoticeManager"] = factory();
	else
		root["ArtsNoticeManager"] = factory();
})(this, () => {
return /******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/ts/core/app.ts":
/*!****************************!*\
  !*** ./src/ts/core/app.ts ***!
  \****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ArtsLicenseManager: () => (/* binding */ ArtsLicenseManager)
/* harmony export */ });
/* harmony import */ var _services__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./services */ "./src/ts/core/services/LicenseService.ts");
/* harmony import */ var _services__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./services */ "./src/ts/core/services/UIService.ts");
/* harmony import */ var _services__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./services */ "./src/ts/core/services/EmailModalService.ts");

class ArtsLicenseManager {
    config;
    elements;
    licenseService;
    uiService;
    emailModalService = null;
    formAction = '';
    formMethod = 'POST';
    constructor() {
        // Initialize configuration
        this.config = {
            selectors: {
                form: '#arts-license-form',
                clearButton: '[data-ajax-action="clear_license"]',
                adminNotice: '#license-notice',
                noticeText: '.license-notice-text'
            },
            classes: {
                fadeOut: 'license-info-fade-out',
                hidden: 'license-hidden',
                colorActive: 'license-color-active',
                colorExpired: 'license-color-expired',
                noticeSuccess: 'notice-success',
                noticeError: 'notice-error',
                noticeWarning: 'notice-warning',
                noticeInfo: 'notice-info',
                disabled: 'disabled',
                updating: 'updating'
            },
            animation: {
                duration: 0.2,
                stagger: 0.02
            }
        };
        // Setup services
        this.licenseService = new _services__WEBPACK_IMPORTED_MODULE_0__.LicenseService();
        this.uiService = new _services__WEBPACK_IMPORTED_MODULE_1__.UIService(this.config);
        // Initialize elements
        this.elements = {
            form: document.querySelector(this.config.selectors.form),
            clearButton: null,
            notice: document.querySelector(this.config.selectors.adminNotice),
            noticeText: null
        };
        // Initialize the license manager
        this.init();
    }
    /**
     * Gets a localized string from WordPress
     * @param key String key
     * @param fallback Fallback string if key not found
     */
    getString(key, fallback = '') {
        const params = window.artsLicenseManagerParams;
        return params?.strings?.[key] || fallback;
    }
    /**
     * Initialize the license manager
     */
    init() {
        // Initialize email modal service if params are available
        if (window.artsLicenseManagerParams) {
            const modalConfig = {
                apiUrl: window.artsLicenseManagerParams.email_api_url,
                strings: window.artsLicenseManagerParams.strings,
                urls: window.artsLicenseManagerParams.urls
            };
            this.emailModalService = new _services__WEBPACK_IMPORTED_MODULE_2__.EmailModalService(modalConfig);
            // Set up owner update callback to handle successful email registration without page reload
            this.emailModalService.setOwnerUpdateCallback((ownerData) => {
                const ownerBlockEl = document.querySelector('.owner-block');
                if (ownerBlockEl) {
                    this.uiService.updateOwnerInfo(ownerBlockEl, ownerData, 0);
                }
            });
            // Expose global API
            window.ArtsLicenseManager = {
                openEmailModal: (purchaseCode, source = 'license_screen') => this.emailModalService.openModal(purchaseCode, source)
            };
        }
        // Setup connect to account button
        this.setupConnectButton();
        // Get notice text element
        if (this.elements.notice) {
            this.elements.noticeText = this.elements.notice.querySelector(this.config.selectors.noticeText);
            if (this.elements.noticeText) {
                this.elements.noticeText.classList.add('license-notice-text');
            }
        }
        // Initialize form if it exists
        if (this.elements.form) {
            this.elements.clearButton = this.elements.form.querySelector(this.config.selectors.clearButton);
            this.formAction = this.elements.form.getAttribute('data-action-ajax') || '';
            this.formMethod = this.elements.form.getAttribute('method') || 'POST';
            this.setupFormListeners();
        }
    }
    /**
     * Set up connect to account button using event delegation
     */
    setupConnectButton() {
        if (this.emailModalService) {
            // Use event delegation on document to handle dynamically created buttons
            document.addEventListener('click', (event) => {
                const target = event.target;
                // Check if clicked element is our connect button
                if (target && target.id === 'connect-to-account-btn') {
                    event.preventDefault();
                    const source = target.getAttribute('data-source') || 'license_screen';
                    // Get the license key from the form or from a data attribute
                    const licenseKeyInput = document.querySelector('[name*="_license_key"]');
                    const licenseKey = licenseKeyInput?.value || '';
                    if (licenseKey && this.emailModalService) {
                        this.emailModalService.openModal(licenseKey, source);
                    }
                }
            });
        }
    }
    /**
     * Set up form event listeners
     */
    setupFormListeners() {
        if (!this.elements.form)
            return;
        // Form submit event
        this.elements.form.addEventListener('submit', (event) => {
            const submitterEl = event.submitter;
            const formData = new FormData(this.elements.form);
            if (submitterEl) {
                const ajaxAction = submitterEl.getAttribute('data-ajax-action');
                const actionName = submitterEl.getAttribute('name');
                if (actionName) {
                    formData.set('action', actionName);
                }
                if (ajaxAction === 'refresh_license') {
                    event.preventDefault();
                    this.refreshLicense(submitterEl, formData);
                }
                else if (ajaxAction === 'clear_license') {
                    event.preventDefault();
                    this.clearLicense(submitterEl, formData);
                }
                else if (ajaxAction === 'activate_license') {
                    event.preventDefault();
                    this.activateLicense(submitterEl, formData);
                }
                else if (ajaxAction === 'deactivate_license') {
                    event.preventDefault();
                    this.deactivateLicense(submitterEl, formData);
                }
            }
        });
        // Clear button click event
        if (this.elements.clearButton) {
            this.elements.clearButton.addEventListener('click', (event) => {
                event.preventDefault();
                const submitEvent = new CustomEvent('submit', { bubbles: true, cancelable: true });
                // Set submitter property
                Object.defineProperty(submitEvent, 'submitter', {
                    value: this.elements.clearButton,
                    writable: false
                });
                this.elements.form?.dispatchEvent(submitEvent);
            });
        }
    }
    /**
     * Refresh license
     * @param currentTargetEl The button that triggered the action
     * @param formData Form data to send
     */
    async refreshLicense(currentTargetEl, formData) {
        if (!this.elements.form)
            return;
        const formUpdatedFields = [...this.elements.form.querySelectorAll('.license-info')];
        const ownerBlockEl = document.querySelector('.owner-block');
        const supportForumEl = formUpdatedFields.find((el) => el.id === 'license-support-forum');
        const renewSupportEl = formUpdatedFields.find((el) => el.id === 'license-renew-support');
        const dateSupportedUntilEl = formUpdatedFields.find((el) => el.id === 'license-date-supported-until');
        // Total fields for stagger calculation (includes owner block as position 1)
        const totalFields = formUpdatedFields.length + (ownerBlockEl ? 1 : 0);
        try {
            this.uiService.toggleFetchPending(currentTargetEl, true);
            const responseData = await this.licenseService.sendRequest(this.formAction, this.formMethod, formData);
            // Check if redirection is needed
            if (this.licenseService.handleRedirect(responseData)) {
                return;
            }
            if (responseData.success) {
                const { data } = responseData;
                // Update license info fields
                this.uiService.updateLicenseInfo(formUpdatedFields, data);
                // Update support info fields
                this.uiService.updateSupportInfo(supportForumEl || null, renewSupportEl || null, dateSupportedUntilEl || null, data, totalFields);
                // Update owner info (position 1 in stagger sequence)
                this.uiService.updateOwnerInfo(ownerBlockEl, data, 1);
                // Update admin notice
                if (this.elements.notice && this.elements.noticeText && data.message) {
                    this.uiService.updateAdminNotice(this.elements.notice, this.elements.noticeText, data.message);
                }
            }
        }
        catch (error) {
            console.error(error);
            alert(this.getString('error_refresh_license', 'An error occurred while refreshing the license.'));
        }
        finally {
            this.uiService.toggleFetchPending(currentTargetEl, false);
        }
    }
    /**
     * Clear license
     * @param currentTargetEl The button that triggered the action
     * @param formData Form data to send
     */
    async clearLicense(currentTargetEl, formData) {
        if (!this.elements.form)
            return;
        const licenseInputWrappers = [
            ...this.elements.form.querySelectorAll('.license-input-wrapper')
        ];
        try {
            this.uiService.toggleFetchPending(currentTargetEl, true);
            const responseData = await this.licenseService.sendRequest(this.formAction, this.formMethod, formData);
            // Check if redirection is needed
            if (this.licenseService.handleRedirect(responseData)) {
                return;
            }
            if (responseData.success) {
                const { data } = responseData;
                // Update license input fields
                this.uiService.updateLicenseInputs(licenseInputWrappers, currentTargetEl, data);
                // Update admin notice
                if (this.elements.notice && this.elements.noticeText && data.message) {
                    this.uiService.updateAdminNotice(this.elements.notice, this.elements.noticeText, data.message);
                }
            }
        }
        catch (error) {
            console.error(error);
            alert(this.getString('error_clear_license', 'An error occurred while clearing the license.'));
        }
        finally {
            this.uiService.toggleFetchPending(currentTargetEl, false);
        }
    }
    /**
     * Activate license
     * @param currentTargetEl The button that triggered the action
     * @param formData Form data to send
     */
    async activateLicense(currentTargetEl, formData) {
        if (!this.elements.form)
            return;
        try {
            this.uiService.toggleFetchPending(currentTargetEl, true);
            // Also disable the license input field during activation (both hidden and visible inputs)
            const licenseInputs = this.elements.form.querySelectorAll('[name*="_license_key"]');
            licenseInputs.forEach((input) => {
                if (input.type !== 'hidden') {
                    input.disabled = true;
                }
            });
            const responseData = await this.licenseService.sendRequest(this.formAction, this.formMethod, formData);
            if (responseData.success) {
                const { data } = responseData;
                // Update admin notice with success
                if (this.elements.notice && this.elements.noticeText && data.message) {
                    this.uiService.updateAdminNotice(this.elements.notice, this.elements.noticeText, data.message, 'success');
                }
                // Transform the form from deactivated to activated state dynamically
                // Pass button to callback so it gets re-enabled after view switching completes
                this.transformToActivatedState(data, currentTargetEl);
                // Check if modal should auto-open based on should_prompt_email flag
                if (this.emailModalService && data.license_key && data.should_prompt_email) {
                    setTimeout(() => {
                        this.emailModalService?.openModal(data.license_key, 'post_activation');
                    }, 100);
                }
                // Success case: don't re-enable button here, let the view switching callback handle it
                return;
            }
            else {
                // Show error notice without redirecting
                if (this.elements.notice && this.elements.noticeText) {
                    const errorMessage = responseData.data?.message || 'An error occurred while activating the license.';
                    this.uiService.updateAdminNotice(this.elements.notice, this.elements.noticeText, errorMessage, 'error');
                }
                // Re-enable form on error
                this.enableLicenseInputs();
            }
        }
        catch (error) {
            console.error(error);
            // Use alert for consistency with refresh pattern
            alert(this.getString('error_activate_license', 'Unable to activate license. Please check your connection and license key.'));
            // Re-enable form on error
            this.enableLicenseInputs();
        }
        finally {
            // Only re-enable button if we didn't have a successful activation (which returns early)
            this.uiService.toggleFetchPending(currentTargetEl, false);
        }
    }
    /**
     * Deactivate license
     * @param currentTargetEl The button that triggered the action
     * @param formData Form data to send
     */
    async deactivateLicense(currentTargetEl, formData) {
        if (!this.elements.form)
            return;
        try {
            this.uiService.toggleFetchPending(currentTargetEl, true);
            // Also disable any license input fields during deactivation
            const licenseInputs = this.elements.form.querySelectorAll('[name*="_license_key"]');
            licenseInputs.forEach((input) => {
                if (input.type !== 'hidden') {
                    input.disabled = true;
                }
            });
            const responseData = await this.licenseService.sendRequest(this.formAction, this.formMethod, formData);
            if (responseData.success) {
                const { data } = responseData;
                // Update admin notice with success
                if (this.elements.notice && this.elements.noticeText && data.message) {
                    this.uiService.updateAdminNotice(this.elements.notice, this.elements.noticeText, data.message, 'success');
                }
                // Transform the form from activated to deactivated state dynamically
                // Pass button to callback so it gets re-enabled after view switching completes
                this.transformToDeactivatedState(data, currentTargetEl);
                // Success case: don't re-enable button here, let the view switching callback handle it
                return;
            }
            else {
                // Show error notice without redirecting
                if (this.elements.notice && this.elements.noticeText) {
                    const errorMessage = responseData.data?.message || 'An error occurred while deactivating the license.';
                    this.uiService.updateAdminNotice(this.elements.notice, this.elements.noticeText, errorMessage, 'error');
                }
                // Re-enable form on error
                this.enableLicenseInputs();
            }
        }
        catch (error) {
            console.error(error);
            // Use alert for consistency with refresh pattern
            alert(this.getString('error_deactivate_license', 'Unable to deactivate license. Please check your connection.'));
            // Re-enable form on error
            this.enableLicenseInputs();
        }
        finally {
            // Only re-enable button if we didn't have a successful deactivation (which returns early)
            this.uiService.toggleFetchPending(currentTargetEl, false);
        }
    }
    /**
     * Enables license input fields after activation/deactivation is complete
     */
    enableLicenseInputs() {
        if (!this.elements.form)
            return;
        const licenseInputs = this.elements.form.querySelectorAll('[name*="_license_key"]');
        licenseInputs.forEach((input) => {
            if (input.type !== 'hidden') {
                input.disabled = false;
            }
        });
    }
    /**
     * Transforms the form from deactivated to activated state dynamically
     * @param data License data from activation response
     * @param buttonEl The button element to re-enable after view switching completes
     */
    transformToActivatedState(data, buttonEl) {
        if (!this.elements.form)
            return;
        // Update the hidden license key input value
        const hiddenLicenseInput = this.elements.form.querySelector('input[type="hidden"][name*="_license_key"]');
        if (hiddenLicenseInput) {
            hiddenLicenseInput.value = data.license_key || '';
        }
        // Use UIService to smoothly switch to activated view
        this.uiService.switchToActivatedView(data, () => {
            // Re-enable form after view switching animation is complete
            this.enableLicenseInputs();
            // Re-enable the button after view switching is complete
            if (buttonEl) {
                this.uiService.toggleFetchPending(buttonEl, false);
            }
        });
    }
    /**
     * Transforms the form from activated to deactivated state dynamically
     * @param data License data from deactivation response
     * @param buttonEl The button element to re-enable after view switching completes
     */
    transformToDeactivatedState(data, buttonEl) {
        if (!this.elements.form)
            return;
        // Update the hidden license key input value
        const hiddenLicenseInput = this.elements.form.querySelector('input[type="hidden"][name*="_license_key"]');
        if (hiddenLicenseInput) {
            hiddenLicenseInput.value = data.license_key || '';
        }
        // Use UIService to smoothly switch to deactivated view
        this.uiService.switchToDeactivatedView(data.license_key || '', () => {
            // Re-enable form after view switching animation is complete
            this.enableLicenseInputs();
            // Re-enable the button after view switching is complete
            if (buttonEl) {
                this.uiService.toggleFetchPending(buttonEl, false);
            }
        });
    }
}


/***/ }),

/***/ "./src/ts/core/services/EmailModalService.ts":
/*!***************************************************!*\
  !*** ./src/ts/core/services/EmailModalService.ts ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   EmailModalService: () => (/* binding */ EmailModalService)
/* harmony export */ });
class EmailModalService {
    modal = null;
    overlay = null;
    container = null;
    form = null;
    purchaseCodeField = null;
    emailField = null;
    submitBtn = null;
    skipBtn = null;
    closeBtn = null;
    legalCheckbox = null;
    marketingCheckbox = null;
    notice = null;
    errorDiv = null;
    legalError = null;
    isSubmitting = false;
    previousFocus = null;
    config;
    onOwnerUpdate = null;
    constructor(config) {
        this.config = config;
    }
    /**
     * Gets a localized string from WordPress
     * @param key String key
     * @param fallback Fallback string if key not found
     */
    getString(key, fallback = '') {
        const params = window.artsLicenseManagerParams;
        return params?.strings?.[key] || fallback;
    }
    /**
     * Sets a callback function to handle owner updates after successful email registration
     * @param callback Callback function to update owner info without page reload
     */
    setOwnerUpdateCallback(callback) {
        this.onOwnerUpdate = callback;
    }
    /**
     * Opens the email modal
     */
    async openModal(purchaseCode, source = 'license_screen') {
        if (!this.modal) {
            this.createModal();
        }
        if (!this.modal)
            return;
        // Reset modal to clean state
        this.resetModalState();
        // Populate the purchase code field
        if (this.purchaseCodeField) {
            this.purchaseCodeField.value = purchaseCode;
        }
        // Store previous focus
        this.previousFocus = document.activeElement;
        // Show modal
        this.modal.setAttribute('aria-hidden', 'false');
        this.modal.style.display = 'block';
        // Add visible class after a brief delay for animation
        setTimeout(() => {
            if (this.container) {
                this.container.classList.add('arts-email-modal__container--visible');
            }
        }, 50);
        // Focus email field
        setTimeout(() => {
            if (this.emailField) {
                this.emailField.focus();
            }
        }, 350);
        // Setup event listeners
        this.setupEventListeners(purchaseCode, source);
    }
    /**
     * Resets the modal to a clean state
     */
    resetModalState() {
        // Reset submission state
        this.isSubmitting = false;
        // Enable all form elements
        this.setFormDisabled(false);
        // Clear email field
        if (this.emailField) {
            this.emailField.value = '';
        }
        // Clear checkboxes
        if (this.legalCheckbox) {
            this.legalCheckbox.checked = false;
        }
        if (this.marketingCheckbox) {
            this.marketingCheckbox.checked = false;
        }
        // Hide all notices and error messages
        if (this.notice) {
            this.notice.style.display = 'none';
            this.notice.textContent = '';
            this.notice.className = 'arts-email-modal__notice';
        }
        if (this.errorDiv) {
            this.errorDiv.style.display = 'none';
        }
        if (this.legalError) {
            this.legalError.style.display = 'none';
        }
        // Re-validate form to set proper button states
        this.validateForm();
    }
    /**
     * Closes the email modal
     */
    closeModal() {
        if (!this.modal || !this.container)
            return;
        // Remove event listeners
        this.removeEventListeners();
        // Hide modal with animation
        this.container.classList.remove('arts-email-modal__container--visible');
        setTimeout(() => {
            if (this.modal) {
                this.modal.setAttribute('aria-hidden', 'true');
                this.modal.style.display = 'none';
            }
            // Restore focus
            if (this.previousFocus) {
                ;
                this.previousFocus.focus();
            }
        }, 250);
    }
    /**
     * Creates the modal HTML structure
     */
    createModal() {
        const modalHTML = `
      <div id="arts-email-modal" class="arts-email-modal" role="dialog" aria-modal="true" aria-labelledby="arts-email-modal-title" aria-describedby="arts-email-modal-desc" aria-hidden="true" style="display: none;">
        <div class="arts-email-modal__overlay"></div>
        <div class="arts-email-modal__container">
          <div class="arts-email-modal__content">
            <button type="button" class="arts-email-modal__close" aria-label="${this.config.strings.close_modal}">&times;</button>

            <p id="arts-email-modal-desc" class="screen-reader-text">
              ${this.config.strings.modal_description}
            </p>

            <div class="arts-email-modal__header">
              <div class="arts-email-modal__icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 -960 960 960" aria-hidden="true" focusable="false">
                  <g fill="currentColor">
                    <path d="M680-160v-120H560v-80h120v-120h80v120h120v80H760v120h-80Z"/>
                    <path d="M440-280H280q-83 0-141.5-58.5T80-480q0-83 58.5-141.5T280-680h160v80H280q-50 0-85 35t-35 85q0 50 35 85t85 35h160v80Z"/>
                    <path d="M320-440v-80h320v80H320Z"/>
                    <path d="M880-480h-80q0-50-35-85t-85-35H520v-80h160q83 0 141.5 58.5T880-480Z"/>
                  </g>
                </svg>
              </div>
              <h2 id="arts-email-modal-title">${this.config.strings.modal_title}</h2>
            </div>

            <form class="arts-email-modal__form" method="post">
              <div class="arts-email-modal__field">
                <label for="arts-purchase-code">${this.getString('purchase_code_label', 'Purchase Code')}</label>
                <input type="text" id="arts-purchase-code" name="purchase_code" readonly tabindex="-1">
              </div>
              
              <div class="arts-email-modal__field">
                <label for="arts-capture-email">${this.config.strings.email_label}</label>
                <input type="email" id="arts-capture-email" name="email" autocomplete="email" inputmode="email" autocapitalize="off" spellcheck="false" required aria-describedby="email-help">
                <div class="arts-email-modal__field-error" style="display: none;">
                  ${this.config.strings.enter_valid_email}
                </div>
              </div>
              
              <label class="arts-email-modal__legal">
                <input type="checkbox" name="agree_legal" id="agree_legal" required>
                <span>
                  ${this.config.strings.agree_legal
            .replace('%1$s', `<a href="${this.config.urls.terms_of_service}" target="_blank" rel="noopener">${this.config.strings.terms_of_service}</a>`)
            .replace('%2$s', `<a href="${this.config.urls.privacy_policy}" target="_blank" rel="noopener">${this.config.strings.privacy_policy}</a>`)}
                </span>
              </label>

              <label class="arts-email-modal__marketing">
                <input type="checkbox" name="marketing_consent" id="marketing_consent">
                ${this.config.strings.marketing_consent}
              </label>

              <div class="arts-email-modal__legal-error" id="legal-error" role="alert" aria-live="polite" style="display:none">
                ${this.config.strings.agree_to_continue}
              </div>

              <div class="arts-email-modal__actions">
                <button type="submit" class="arts-license-button arts-license-button--primary arts-license-button--fullwidth arts-email-modal__submit" disabled>
                  <span class="arts-license-button__text">${this.config.strings.continue}</span>
                  <span class="arts-license-button__spinner" style="display: none;">⏳</span>
                </button>
                <button type="button" class="arts-license-button arts-email-modal__skip">${this.config.strings.skip_for_now}</button>
              </div>

              <div class="arts-email-modal__notice" aria-live="polite" style="display: none;"></div>
            </form>

            <p class="arts-email-modal__privacy">
              ${this.config.strings.creating_account_info.replace('%s', `<a href="${this.config.urls.account_site}" target="_blank" rel="noopener">${this.config.urls.account_site}</a>`)}
            </p>
          </div>
        </div>
      </div>
    `;
        // Insert modal into document body
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        // Get references to modal elements
        this.modal = document.getElementById('arts-email-modal');
        this.overlay = this.modal?.querySelector('.arts-email-modal__overlay');
        this.container = this.modal?.querySelector('.arts-email-modal__container');
        this.form = this.modal?.querySelector('.arts-email-modal__form');
        this.purchaseCodeField = this.modal?.querySelector('#arts-purchase-code');
        this.emailField = this.modal?.querySelector('#arts-capture-email');
        this.submitBtn = this.modal?.querySelector('.arts-email-modal__submit');
        this.skipBtn = this.modal?.querySelector('.arts-email-modal__skip');
        this.closeBtn = this.modal?.querySelector('.arts-email-modal__close');
        this.legalCheckbox = this.modal?.querySelector('#agree_legal');
        this.marketingCheckbox = this.modal?.querySelector('#marketing_consent');
        this.notice = this.modal?.querySelector('.arts-email-modal__notice');
        this.errorDiv = this.modal?.querySelector('.arts-email-modal__field-error');
        this.legalError = this.modal?.querySelector('#legal-error');
    }
    /**
     * Sets up event listeners for modal interaction
     */
    setupEventListeners(purchaseCode, source) {
        if (!this.modal)
            return;
        // Email validation
        this.emailField?.addEventListener('input', () => this.validateForm());
        this.emailField?.addEventListener('blur', () => this.validateForm());
        this.legalCheckbox?.addEventListener('change', () => this.validateForm());
        // Form submission
        this.form?.addEventListener('submit', (e) => this.handleSubmit(e, purchaseCode, source));
        // Close actions
        this.skipBtn?.addEventListener('click', () => this.closeModal());
        this.closeBtn?.addEventListener('click', () => this.closeModal());
        this.overlay?.addEventListener('click', () => this.closeModal());
        // Keyboard handling
        document.addEventListener('keydown', this.handleKeydown.bind(this));
    }
    /**
     * Removes event listeners
     */
    removeEventListeners() {
        document.removeEventListener('keydown', this.handleKeydown.bind(this));
    }
    /**
     * Validates the form and enables/disables submit button
     */
    validateForm() {
        if (!this.emailField || !this.legalCheckbox || !this.submitBtn)
            return false;
        const email = this.emailField.value.trim();
        const isEmailValid = this.emailField.checkValidity() && email.length > 0;
        const isLegalChecked = this.legalCheckbox.checked;
        // Email validation feedback
        if (this.errorDiv) {
            if (email.length > 0 && !isEmailValid) {
                this.errorDiv.style.display = 'block';
                this.emailField.classList.add('error');
            }
            else {
                this.errorDiv.style.display = 'none';
                this.emailField.classList.remove('error');
            }
        }
        const isValid = isEmailValid && isLegalChecked;
        this.submitBtn.disabled = !isValid;
        return isValid;
    }
    /**
     * Handles form submission
     */
    async handleSubmit(event, purchaseCode, source) {
        event.preventDefault();
        if (this.isSubmitting || !this.validateForm())
            return;
        this.isSubmitting = true;
        // Disable all form elements during submission
        this.setFormDisabled(true);
        if (this.notice) {
            this.notice.style.display = 'none';
        }
        if (this.legalError && !this.legalCheckbox?.checked) {
            this.legalError.style.display = 'block';
            this.resetFormAfterError();
            return;
        }
        if (this.legalError) {
            this.legalError.style.display = 'none';
        }
        try {
            const submitData = {
                email: this.emailField?.value.trim() || '',
                key: purchaseCode,
                marketing_opt_in: this.marketingCheckbox?.checked || false,
                privacy_policy_agreed: this.legalCheckbox?.checked || false,
                terms_of_service_agreed: this.legalCheckbox?.checked || false,
                source: source
            };
            const response = await fetch(this.config.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(submitData)
            });
            const result = await response.json();
            if (response.ok && result.success) {
                // Update email_link_state to 'real' to indicate successful linking
                this.updateEmailLinkState('real');
                this.showSuccess(result.message ||
                    this.getString('email_success_message', 'Check your inbox to confirm your email. You can continue setup now.'));
                // Close modal and update owner info via callback instead of page reload
                setTimeout(() => {
                    this.closeModal();
                    // If callback is set, update owner info via AJAX instead of page reload
                    if (this.onOwnerUpdate) {
                        const ownerData = {
                            email_link_state: 'real',
                            masked_email: result.masked_email || '', // Include masked email from API response
                            order_history_url: result.order_history_url || '' // Include order history URL from API response
                        };
                        this.onOwnerUpdate(ownerData);
                    }
                    else {
                        // Fallback to page reload if callback is not set
                        window.location.reload();
                    }
                }, 1500);
            }
            else {
                let errorMessage = result.message ||
                    this.getString('generic_error_message', 'An error occurred. Please try again.');
                // Handle specific HTTP status codes
                if (response.status === 409) {
                    // Email already in use - this is an error, not success
                    if (errorMessage.toLowerCase().includes('email') &&
                        errorMessage.toLowerCase().includes('already')) {
                        this.showError(errorMessage);
                        this.resetFormAfterError();
                        return;
                    }
                    // License already linked - treat as success
                    this.updateEmailLinkState('real');
                    this.showSuccess(result.message || 'This license is already linked to your account.');
                    // Close modal and update owner info via callback instead of page reload
                    setTimeout(() => {
                        this.closeModal();
                        // If callback is set, update owner info via AJAX instead of page reload
                        if (this.onOwnerUpdate) {
                            const ownerData = {
                                email_link_state: 'real',
                                masked_email: result.masked_email || '', // Include masked email from API response
                                order_history_url: result.order_history_url || '' // Include order history URL from API response
                            };
                            this.onOwnerUpdate(ownerData);
                        }
                        else {
                            // Fallback to page reload if callback is not set
                            window.location.reload();
                        }
                    }, 1500);
                    return;
                }
                else if (response.status === 429) {
                    const retryMinutes = result.retry_after ? Math.ceil(result.retry_after / 60) : 60;
                    errorMessage = this.getString('too_many_requests_error', 'Too many requests. Please wait %s minutes before trying again.').replace('%s', retryMinutes.toString());
                }
                else if (response.status === 404) {
                    errorMessage = this.getString('license_not_found_error', 'License not found. Please check your purchase code.');
                }
                else if (response.status === 502) {
                    errorMessage = this.getString('email_error_generic', "We couldn't send the email. Try again or skip for now.");
                }
                this.showError(errorMessage);
                this.resetFormAfterError();
            }
        }
        catch (error) {
            this.showError(this.getString('email_error_generic', "We couldn't send the email. Try again or skip for now."));
            this.resetFormAfterError();
        }
    }
    /**
     * Shows success message
     */
    showSuccess(message) {
        if (this.notice) {
            this.notice.className = 'arts-email-modal__notice arts-email-modal__notice--success';
            this.notice.textContent = message;
            this.notice.style.display = 'block';
        }
    }
    /**
     * Shows error message
     */
    showError(message) {
        if (this.notice) {
            this.notice.className = 'arts-email-modal__notice arts-email-modal__notice--error';
            this.notice.textContent = message;
            this.notice.style.display = 'block';
        }
    }
    /**
     * Sets form disabled state
     */
    setFormDisabled(disabled) {
        if (this.purchaseCodeField) {
            // Purchase code field is always readonly, no need to change its state
        }
        if (this.emailField) {
            this.emailField.disabled = disabled;
        }
        if (this.submitBtn) {
            this.submitBtn.disabled = disabled;
            if (disabled) {
                this.submitBtn.classList.add('arts-license-button--loading');
            }
            else {
                this.submitBtn.classList.remove('arts-license-button--loading');
            }
        }
        if (this.skipBtn) {
            this.skipBtn.disabled = disabled;
        }
        if (this.legalCheckbox) {
            this.legalCheckbox.disabled = disabled;
        }
        if (this.marketingCheckbox) {
            this.marketingCheckbox.disabled = disabled;
        }
    }
    /**
     * Resets form state after error
     */
    resetFormAfterError() {
        this.isSubmitting = false;
        this.setFormDisabled(false);
        // Re-validate form to properly set button state
        this.validateForm();
    }
    /**
     * Updates the email link state option
     */
    updateEmailLinkState(state) {
        // Send a simple request to update the option
        const formData = new FormData();
        formData.append('action', 'arts_update_email_link_state');
        formData.append('email_link_state', state);
        formData.append('nonce', window.artsLicenseManagerParams?.nonce || '');
        // Use navigator.sendBeacon for reliability during page unload, fallback to fetch
        const body = new URLSearchParams(formData);
        if (navigator.sendBeacon) {
            navigator.sendBeacon(window.location.origin + '/wp-admin/admin-ajax.php', body);
        }
        else {
            fetch(window.location.origin + '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: body,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            }).catch(() => {
                // Ignore errors since we're refreshing anyway
            });
        }
    }
    /**
     * Handles keyboard events
     */
    handleKeydown(event) {
        if (!this.modal || this.modal.style.display === 'none')
            return;
        // ESC key closes modal
        if (event.key === 'Escape') {
            this.closeModal();
            return;
        }
        // Tab key focus trapping
        if (event.key === 'Tab') {
            const focusableElements = this.modal.querySelectorAll('input, button, a[href]');
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];
            if (event.shiftKey) {
                if (document.activeElement === firstElement) {
                    lastElement.focus();
                    event.preventDefault();
                }
            }
            else {
                if (document.activeElement === lastElement) {
                    firstElement.focus();
                    event.preventDefault();
                }
            }
        }
    }
}


/***/ }),

/***/ "./src/ts/core/services/LicenseService.ts":
/*!************************************************!*\
  !*** ./src/ts/core/services/LicenseService.ts ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   LicenseService: () => (/* binding */ LicenseService)
/* harmony export */ });
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils */ "./src/ts/core/utils/getValidUrl.ts");

class LicenseService {
    /**
     * Sends a request to the license API
     * @param url API endpoint URL
     * @param method HTTP method
     * @param formData Form data to send
     * @returns Promise with license response
     */
    async sendRequest(url, method, formData) {
        try {
            const response = await fetch(url, {
                method,
                body: formData
            });
            return await response.json();
        }
        catch (error) {
            console.error('License API error:', error);
            throw error;
        }
    }
    /**
     * Handles redirect from license response if needed
     * @param responseData The license response data
     * @returns boolean indicating if redirection was performed
     */
    handleRedirect(responseData) {
        if (!responseData.success && responseData.data.location) {
            const validUrl = (0,_utils__WEBPACK_IMPORTED_MODULE_0__.getValidUrl)(responseData.data.location);
            if (validUrl) {
                window.location.href = validUrl;
                return true;
            }
            else {
                window.location.reload();
                return true;
            }
        }
        return false;
    }
}


/***/ }),

/***/ "./src/ts/core/services/UIService.ts":
/*!*******************************************!*\
  !*** ./src/ts/core/services/UIService.ts ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   UIService: () => (/* binding */ UIService)
/* harmony export */ });
class UIService {
    config;
    constructor(config) {
        this.config = config;
    }
    /**
     * Gets a localized string from WordPress
     * @param key String key
     * @param fallback Fallback string if key not found
     */
    getString(key, fallback = '') {
        const params = window.artsLicenseManagerParams;
        return params?.strings?.[key] || fallback;
    }
    /**
     * Toggles loading/updating state on a button
     * @param element Button element
     * @param updating Whether the button is in updating state
     */
    toggleFetchPending(element, updating = false) {
        element.classList.toggle(this.config.classes.disabled, updating);
        element.classList.toggle(this.config.classes.updating, updating);
    }
    /**
     * Updates license information elements with new data
     * @param formUpdatedFields Elements to update
     * @param data License data
     */
    updateLicenseInfo(formUpdatedFields, data) {
        formUpdatedFields.forEach((el, index) => {
            el.classList.add(this.config.classes.fadeOut);
            // Update content based on element ID
            switch (el.id) {
                case 'license-status':
                    el.textContent = data.status || '';
                    break;
                case 'license-key':
                    el.textContent = data.key || '';
                    break;
                case 'license-expires':
                    el.textContent = data.expires || '';
                    break;
                case 'license-activations-left':
                    el.textContent = data.activations_left || '';
                    break;
                case 'license-site-count':
                    el.textContent = data.site_count || '';
                    break;
                case 'license-limit':
                    el.textContent = data.license_limit || '';
                    break;
                case 'license-date-purchased':
                    el.textContent = data.date_purchased || '';
                    break;
                case 'license-date-updates-provided-until':
                    el.textContent = data.date_updates_provided_until || '';
                    break;
            }
            // Remove fade-out class after animation completes
            setTimeout(() => {
                if (el.id !== 'license-support-forum' &&
                    el.id !== 'license-renew-support' &&
                    el.id !== 'license-date-supported-until') {
                    el.classList.remove(this.config.classes.fadeOut);
                }
                // Handle site count description visibility based on is_local flag
                if (el.id === 'license-site-count') {
                    const descriptionEl = document.getElementById('license-site-count-description');
                    if (descriptionEl && data.is_local !== undefined) {
                        if (data.is_local) {
                            descriptionEl.classList.remove(this.config.classes.hidden);
                        }
                        else {
                            descriptionEl.classList.add(this.config.classes.hidden);
                        }
                    }
                }
            }, this.config.animation.duration * 1000 + index * (this.config.animation.stagger * 1000));
        });
    }
    /**
     * Updates support information elements
     * @param supportForumEl Support forum element
     * @param renewSupportEl Renew support element
     * @param dateSupportedUntilEl Support date element
     * @param data License data
     * @param formFieldCount Total number of fields for animation timing
     */
    updateSupportInfo(supportForumEl, renewSupportEl, dateSupportedUntilEl, data, formFieldCount) {
        if (!supportForumEl || !renewSupportEl || !dateSupportedUntilEl)
            return;
        setTimeout(() => {
            const isSupportProvided = data.is_support_provided;
            if (isSupportProvided) {
                renewSupportEl.classList.add(this.config.classes.hidden);
                supportForumEl.classList.remove(this.config.classes.hidden);
                dateSupportedUntilEl.classList.add(this.config.classes.colorActive);
                dateSupportedUntilEl.classList.remove(this.config.classes.colorExpired);
            }
            else {
                supportForumEl.classList.add(this.config.classes.hidden);
                renewSupportEl.classList.remove(this.config.classes.hidden);
                dateSupportedUntilEl.classList.remove(this.config.classes.colorActive);
                dateSupportedUntilEl.classList.add(this.config.classes.colorExpired);
            }
            dateSupportedUntilEl.textContent = data.date_supported_until || '';
            supportForumEl.classList.remove(this.config.classes.fadeOut);
            renewSupportEl.classList.remove(this.config.classes.fadeOut);
            dateSupportedUntilEl.classList.remove(this.config.classes.fadeOut);
        }, this.config.animation.duration * 1000 +
            formFieldCount * (this.config.animation.stagger * 1000));
    }
    /**
     * Updates owner block information
     * @param ownerBlockEl Owner block container element
     * @param data License data including email link state and strings
     * @param formFieldCount Total number of fields for animation timing
     */
    updateOwnerInfo(ownerBlockEl, data, staggerIndex) {
        if (!ownerBlockEl)
            return;
        const isLinked = data.email_link_state === 'real';
        // Use provided strings or extract from DOM as fallback
        let ownerStrings = data.owner_strings;
        if (!ownerStrings) {
            // Extract strings from existing DOM elements as fallback
            const connectBtn = document.querySelector('#connect-to-account-btn');
            const helperTexts = ownerBlockEl.querySelectorAll('.description:not(.license-color-active)');
            ownerStrings = {
                linked: this.getString('linked_to_account', 'Linked to Your Account'),
                not_linked: this.getString('not_linked_to_account', 'Not Linked to Your Account'),
                connect: connectBtn?.textContent?.trim() ||
                    this.getString('connect_to_account', 'Connect to Your Account'),
                manage_online: this.getString('manage_account', 'Manage Account'),
                helper_1: helperTexts[0]?.textContent?.trim() || '',
                helper_2: helperTexts[1]?.textContent?.trim() || ''
            };
        }
        // Add fade-out animation
        ownerBlockEl.classList.add(this.config.classes.fadeOut);
        setTimeout(() => {
            // Clear existing content and rebuild
            if (isLinked) {
                // Determine the title based on whether we have masked email
                let linkedTitle = ownerStrings.linked;
                if (data.masked_email && data.masked_email.trim() !== '') {
                    linkedTitle = `Linked to ${data.masked_email}`;
                }
                // Use dynamic URL if available, otherwise fallback to default
                const manageUrl = data.order_history_url && data.order_history_url.trim() !== ''
                    ? data.order_history_url
                    : 'https://artemsemkin.com/login/';
                ownerBlockEl.innerHTML = `
            <h2 class="title">${linkedTitle}</h2>
            <p class="description license-color-active">
              <a href="${manageUrl}" target="_blank" rel="noopener">
                ${ownerStrings.manage_online}
              </a>
            </p>
          `;
            }
            else {
                ownerBlockEl.innerHTML = `
            <h2 class="title">${ownerStrings.not_linked}</h2>
            <p class="description">${ownerStrings.helper_1}</p>
            <p class="description">${ownerStrings.helper_2}</p>
            <br>
            <button type="button" id="connect-to-account-btn" class="button button-primary" data-source="license_screen">
              ${ownerStrings.connect}
            </button>
          `;
            }
            // Remove fade-out class
            ownerBlockEl.classList.remove(this.config.classes.fadeOut);
        }, this.config.animation.duration * 1000 + staggerIndex * (this.config.animation.stagger * 1000));
    }
    /**
     * Updates the admin notice with a message
     * @param notice Notice element
     * @param noticeText Notice text element
     * @param message Message to display
     * @param noticeType Type of notice (success, error, etc)
     */
    updateAdminNotice(notice, noticeText, message, noticeType = 'success') {
        if (!notice || !noticeText || !message)
            return;
        noticeText.classList.add(this.config.classes.fadeOut);
        setTimeout(() => {
            // Remove all notice classes
            notice.classList.remove(this.config.classes.noticeSuccess, this.config.classes.noticeError, this.config.classes.noticeWarning, this.config.classes.noticeInfo);
            // Add appropriate notice class
            switch (noticeType) {
                case 'success':
                    notice.classList.add(this.config.classes.noticeSuccess);
                    break;
                case 'error':
                    notice.classList.add(this.config.classes.noticeError);
                    break;
                case 'warning':
                    notice.classList.add(this.config.classes.noticeWarning);
                    break;
                case 'info':
                    notice.classList.add(this.config.classes.noticeInfo);
                    break;
            }
            noticeText.textContent = message;
            // Show the notice by removing display: none
            notice.style.display = 'block';
            noticeText.classList.remove(this.config.classes.fadeOut);
        }, this.config.animation.duration * 1000);
    }
    /**
     * Updates license input fields after clearing
     * @param licenseInputWrappers Input wrapper elements
     * @param clearButton Clear button element
     * @param data License data
     */
    updateLicenseInputs(licenseInputWrappers, clearButton, data) {
        licenseInputWrappers.forEach((el, index) => {
            el.classList.add(this.config.classes.fadeOut);
            setTimeout(() => {
                const inputEl = el.querySelector('input');
                if (inputEl) {
                    inputEl.value = data.key || '';
                }
                if (!data.status || data.status === 'deactivated') {
                    clearButton.classList.add(this.config.classes.fadeOut);
                    setTimeout(() => {
                        clearButton.classList.add(this.config.classes.hidden);
                        this.toggleFetchPending(clearButton, false);
                    }, this.config.animation.duration * 1000);
                }
                el.classList.remove(this.config.classes.fadeOut);
            }, this.config.animation.duration * 1000 + index * (this.config.animation.stagger * 1000));
        });
    }
    /**
     * Updates the license key row in the activated view
     * @param activatedView The activated view element
     * @param data License data
     * @param staggerIndex Index for animation staggering
     */
    updatePurchaseCodeRow(data) {
        const activatedView = document.getElementById('license-activated-view');
        if (!activatedView)
            return;
        const licenseKeyRow = activatedView.querySelector('tr:first-child');
        if (!licenseKeyRow)
            return;
        const cardEl = licenseKeyRow.querySelector('.card');
        if (!cardEl)
            return;
        // Get the theme slug from existing form elements
        const hiddenInput = document.querySelector('input[name*="_license_key"]');
        const themeSlug = hiddenInput?.name?.replace('_license_key', '') || 'theme';
        // Add fade-out animation
        cardEl.classList.add(this.config.classes.fadeOut);
        setTimeout(() => {
            // Update the card content with proper activated license structure using localized strings
            cardEl.innerHTML = `
        <h2 class="title">${data.key || data.license_key || ''}</h2>
        <p class="description license-color-active">${data.message || this.getString('license_activated', 'License has been activated successfully')}</p>
        <br>
        <input type="submit" class="button button-primary button-large" 
               name="${themeSlug}_license_deactivate" 
               value="${this.getString('deactivate_license', 'Deactivate License')}" 
               data-ajax-action="deactivate_license" />
        <button id="refresh-license-button" class="button button-secondary arts-license-ajax-button right" type="submit"
                name="${themeSlug}_license_refresh" 
                value="${this.getString('refresh_license', 'Refresh License')}" 
                data-ajax-action="refresh_license">
          <span class="arts-license-ajax-button__icon-animated dashicons dashicons-update"></span>
          <span class="arts-license-ajax-button__label">${this.getString('refresh_license', 'Refresh License')}</span>
        </button>
      `;
            // Remove fade-out class
            cardEl.classList.remove(this.config.classes.fadeOut);
        }, this.config.animation.duration * 1000);
    }
    /**
     * Hides the CTA (call-to-action) card when license is activated
     */
    hideLicenseCTACard() {
        const ctaCard = document.getElementById('license-cta-card');
        if (ctaCard) {
            ctaCard.style.display = 'none';
        }
    }
    /**
     * Switches from deactivated view to activated view with smooth animation
     * @param data License data to populate the activated view
     * @param onComplete Optional callback to execute when view switching is complete
     */
    switchToActivatedView(data, onComplete) {
        const deactivatedView = document.getElementById('license-deactivated-view');
        const activatedView = document.getElementById('license-activated-view');
        if (!deactivatedView || !activatedView)
            return;
        // Fade out deactivated view
        deactivatedView.classList.add(this.config.classes.fadeOut);
        setTimeout(() => {
            // Hide deactivated view and show activated view
            deactivatedView.classList.add(this.config.classes.hidden);
            activatedView.classList.remove(this.config.classes.hidden);
            // Update Purchase Code row and hide CTA card
            this.updatePurchaseCodeRow(data);
            this.hideLicenseCTACard();
            // Update data in activated view
            const formUpdatedFields = [...activatedView.querySelectorAll('.license-info')];
            const ownerBlockEl = activatedView.querySelector('.owner-block');
            const supportForumEl = activatedView.querySelector('#license-support-forum');
            const renewSupportEl = activatedView.querySelector('#license-renew-support');
            const dateSupportedUntilEl = activatedView.querySelector('#license-date-supported-until');
            // Update license info fields
            this.updateLicenseInfo(formUpdatedFields, data);
            // Update owner info first (position 1 in stagger sequence, right after Purchase Code)
            if (ownerBlockEl) {
                this.updateOwnerInfo(ownerBlockEl, data, 1);
            }
            // Update support info after owner and license fields
            if (supportForumEl && renewSupportEl && dateSupportedUntilEl) {
                this.updateSupportInfo(supportForumEl, renewSupportEl, dateSupportedUntilEl, data, formUpdatedFields.length);
            }
            // Fade in activated view
            setTimeout(() => {
                deactivatedView.classList.remove(this.config.classes.fadeOut);
                // Call onComplete callback after view switching animation is complete
                if (onComplete) {
                    onComplete();
                }
            }, 50);
        }, this.config.animation.duration * 1000);
    }
    /**
     * Switches from activated view to deactivated view with smooth animation
     * @param licenseKey The license key to show in the input field
     * @param onComplete Optional callback to execute when view switching is complete
     */
    switchToDeactivatedView(licenseKey = '', onComplete) {
        const deactivatedView = document.getElementById('license-deactivated-view');
        const activatedView = document.getElementById('license-activated-view');
        if (!deactivatedView || !activatedView)
            return;
        // Fade out activated view
        activatedView.classList.add(this.config.classes.fadeOut);
        setTimeout(() => {
            // Hide activated view and show deactivated view
            activatedView.classList.add(this.config.classes.hidden);
            deactivatedView.classList.remove(this.config.classes.hidden);
            // Update license input field with the key
            const licenseInput = deactivatedView.querySelector('[name*="_license_key"]:not([type="hidden"])');
            if (licenseInput && licenseKey) {
                licenseInput.value = licenseKey;
            }
            // Fade in deactivated view
            setTimeout(() => {
                activatedView.classList.remove(this.config.classes.fadeOut);
                // Call onComplete callback after view switching animation is complete
                if (onComplete) {
                    onComplete();
                }
            }, 50);
        }, this.config.animation.duration * 1000);
    }
}


/***/ }),

/***/ "./src/ts/core/utils/getValidUrl.ts":
/*!******************************************!*\
  !*** ./src/ts/core/utils/getValidUrl.ts ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getValidUrl: () => (/* binding */ getValidUrl)
/* harmony export */ });
/**
 * Validates and sanitizes a URL to prevent open redirects and XSS
 * @param url The URL to validate
 * @returns A sanitized URL if valid, or empty string if invalid
 */
function getValidUrl(url) {
    try {
        const parsedUrl = new URL(url, window.location.origin);
        // Ensure the URL is relative to current origin
        if (parsedUrl.origin !== window.location.origin) {
            return '';
        }
        // Sanitize URL parts to remove potentially harmful characters
        const sanitizedPathname = parsedUrl.pathname.replace(/[^a-zA-Z0-9/_\-.]/g, '');
        const sanitizedSearch = parsedUrl.search.replace(/[^a-zA-Z0-9&=_\-.?+]/g, '');
        const sanitizedHash = parsedUrl.hash.replace(/[^a-zA-Z0-9&=_\-.?+]/g, '');
        return `${window.location.origin}${sanitizedPathname}${sanitizedSearch}${sanitizedHash}`;
    }
    catch (e) {
        return '';
    }
}


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!*************************!*\
  !*** ./src/ts/index.ts ***!
  \*************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ArtsLicenseManager: () => (/* reexport safe */ _core_app__WEBPACK_IMPORTED_MODULE_0__.ArtsLicenseManager)
/* harmony export */ });
/* harmony import */ var _core_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./core/app */ "./src/ts/core/app.ts");

// Initialize the license manager when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    new _core_app__WEBPACK_IMPORTED_MODULE_0__.ArtsLicenseManager();
});
// Export the class for external use


})();

__webpack_exports__ = __webpack_exports__["default"];
/******/ 	return __webpack_exports__;
/******/ })()
;
});
//# sourceMappingURL=index.umd.js.map