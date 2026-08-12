// Immediately initialize the HospitalCharge namespace
window.HospitalCharge = window.HospitalCharge || {};

// Check if namespace exists - this is now redundant but kept for backward compatibility
if (!window.HospitalCharge) {
    window.HospitalCharge = {};
}

// Only define if not already defined
// eslint-disable-next-line no-undef
if (!HospitalCharge.ReviewViewModel) {
    // eslint-disable-next-line no-undef
    HospitalCharge.ReviewViewModel = class {
        constructor(initialData) {
            this.show = ko.observable(true);
            this.encounters = ko.observableArray(initialData.encounters || []);

            // Initialize procedures with selected and mod_size properties
            this.procedures = ko.observableArray(
                (initialData.procedures || []).map(p => ({
                    ...p,
                    selected: ko.observable(false),
                    mod_size: p.modifiers ? p.modifiers.length + 2 : 10
                }))
            );

            // Initialize issues with selected property
            this.issues = ko.observableArray(
                (initialData.issues || []).map(i => ({
                    ...i,
                    selected: ko.observable(false)
                }))
            );

            this.selectedEncounter = ko.observable(this.encounters()[0] || null);
            this.prevEncounter = ko.observable(null);
            this.patient = initialData.patient || {};

            // Auto-load first encounter's data
            if (this.encounters().length > 0) {
                this.fetchEncounterData(this.selectedEncounter().id);
            }

            // Bind methods to instance
            this.chooseEncounter = this.chooseEncounter.bind(this);
            this.cancel_review = this.cancel_review.bind(this);
            this.add_review = this.add_review.bind(this);
        }

        chooseEncounter = (data, event) => {
            const currentEncounterId = this.selectedEncounter()?.id;
            // Only fetch if we have a valid encounter ID and it's different from the previous one
            if (currentEncounterId && currentEncounterId !== this.prevEncounter()) {
                this.fetchEncounterData(currentEncounterId);
            }
        }

        fetchEncounterData(encounterId) {
            const pid = this.patient?.pid;
            if (!pid) {
                return;
            }

            if (!encounterId) {
                return;
            }

            $.post(
                window.webroot + '/interface/modules/custom_modules/oe-module-hospital-charge/public/index.php/charge-review/encounter-data',
                {
                    pid: pid,
                    encounter: encounterId
                },
                (result) => {
                    try {
                        if (!result || (result.error && typeof result.error === 'string')) {
                            return;
                        }

                        // Add selected and mod_size properties to procedures
                        const procedures = (result.procedures || []).map(p => ({
                            ...p,
                            selected: ko.observable(false),
                            mod_size: p.modifiers ? p.modifiers.length + 2 : 10
                        }));

                        // Add selected property to issues
                        const issues = (result.issues || []).map(i => ({
                            ...i,
                            selected: ko.observable(false)
                        }));

                        // Update the viewmodel with new data
                        this.procedures(procedures);
                        this.issues(issues);
                        this.prevEncounter(encounterId);
                    } catch (e) {
                        console.error('Error processing response:', e);
                    } finally {
                    }
                },
                'json'
            ).fail((xhr) => {
                try {
                    let responseText = xhr.responseText;
                    const jsonStartPos = responseText.indexOf('{');
                    if (jsonStartPos > 0) {
                        responseText = responseText.substring(jsonStartPos);
                    }
                    const responseData = JSON.parse(responseText);
                    if (responseData && responseData.procedures && responseData.issues) {
                        const procedures = (responseData.procedures || []).map(p => ({
                            ...p,
                            selected: ko.observable(false),
                            mod_size: p.modifiers ? p.modifiers.length + 2 : 10
                        }));

                        const issues = (responseData.issues || []).map(i => ({
                            ...i,
                            selected: ko.observable(false)
                        }));

                        this.procedures(procedures);
                        this.issues(issues);
                        this.prevEncounter(encounterId);
                        return;
                    }
                } catch (e) {
                    console.error('Could not parse error response as JSON:', e);
                }
                console.error('Error fetching encounter data', xhr.responseText);
            });
        }

        add_review = (data, event) => {
            event.preventDefault();
            const selectedProcedures = this.procedures().filter(proc => proc.selected());
            const proceduresForForm = selectedProcedures.map(p => ({
                code: p.code,
                description: p.description,
                fee: p.fee,
                modifiers: p.modifiers,
                units: p.units,
                justify: p.justify
            }));
            
            // Collect selected issues (ICD10 codes)
            const selectedIssues = this.issues().filter(issue => issue.selected());
            const issuesForForm = selectedIssues.map(i => ({
                code: i.code,
                description: i.description
            }));
            
            window.parent.postMessage({
                type: 'ADD_REVIEW_ITEMS',
                data: {
                    procedures: proceduresForForm,
                    issues: issuesForForm
                }
            }, '*');
            window.parent.postMessage({
                type: 'CLOSE_REVIEW_MODAL'
            }, '*');
        }

        destroy() {
            try {
                this.encounters([]);
                this.procedures([]);
                this.issues([]);
                this.selectedEncounter(null);
                this.prevEncounter(null);
                if (this.show) {
                    this.show(false);
                }
            } catch (e) {
                console.warn('Cleanup error:', e);
            }
        }

        cancel_review = (data, event) => {
            event.preventDefault();
            this.show(false);
        }
    };
}

// Modified initialization with better error handling
// Use an IIFE to avoid variable redeclaration issues when script runs multiple times
(function() {
    // Wait for DOM to be ready
    $(document).ready(function() {
        // Ensure HospitalCharge namespace exists
        if (typeof window.HospitalCharge === 'undefined') {
            window.HospitalCharge = {};
        }

        // Find the main container that has data-bind attributes
        const container = document.querySelector('[data-bind*="visible: $data.show"]');

        if (!container) {
            return;
        }

        // Clean up previous viewmodel if it exists
        if (window.hospitalChargeViewModel) {
            try {
                window.hospitalChargeViewModel.destroy();
                ko.cleanNode(container);
            } catch (e) {
                console.warn('Cleanup error:', e);
            }
        }

        // Initialize with data from the page
        try {
            window.hospitalChargeViewModel = new HospitalCharge.ReviewViewModel(window.initialData || {});
            ko.applyBindings(window.hospitalChargeViewModel, container);
        } catch (e) {
            console.error('Binding error:', e);
        }

        // Store for global access if needed
        window.currentViewModel = window.hospitalChargeViewModel;
    });
})();
