# OpenEMR Hospital Charge Module

Rapid hospital/office charge entry for OpenEMR: patient search, CPT/HCPCS and ICD-10 lines, modifiers, diagnosis justification, encounter creation, and a charge-review modal.
<img width="1905" height="720" alt="image" src="https://github.com/user-attachments/assets/aa56737e-14ed-493a-945e-8873ea6b019b" />


**Module Manager name:** Hospital Charge v1.0  
**Package version:** 1.0.0 (`version.php`)  
**Namespace:** `Juggernaut\Module\HospitalCharge`  
**Author:** Sherwin Gaddis

## Compatibility

| Target | Status |
|--------|--------|
| **OpenEMR 7.0.x** (7.0.2+) | **Supported** |
| OpenEMR **8.0+** | Not certified (uses `globals.php` / `$GLOBALS` directly) |

- PHP **8.1+** recommended  
- Twig UI; custom App router under `public/index.php`  
- Menu: **Fees → Rapid Charge** (`patients` / `docs` ACL)

## Security (v1.0.0 packaging)

- Front controller requires `patients`/`docs` **or** `acct`/`bill`
- Claim POST handlers verify CSRF token field `csrf_token_form`
- Unauthorized users receive OpenEMR unauthorized Twig page

No module database tables are required for v1.0.0 (uses core `form_encounter` / `billing`).

## Quick install

```bash
cd /path/to/openemr/interface/modules/custom_modules
git clone https://github.com/juggernautsei/oe-module-hospital-charge.git
```

Register and enable in Module Manager. Open **Fees → Rapid Charge**.

---

## Overview

The Hospital Charge Module is a custom OpenEMR module designed to streamline the process of adding and managing charges for hospital services. It provides a user-friendly interface for healthcare providers to quickly add procedure codes (CPT/HCPCS) and diagnosis codes (ICD-10) to patient encounters, calculate totals, and manage billing information.

## Features

- **Rapid Charge Sheet**: A streamlined interface for quickly adding charges to patient encounters
- **Charge Review Panel**: A modal interface that allows users to review and select from available procedures and diagnoses
- **Cross-Frame Communication**: Secure communication between the review modal and the main form using window.postMessage
- **Batch Updates**: Add multiple procedures and diagnoses at once
- **Automatic Total Calculation**: Real-time calculation of charges based on procedure prices and quantities
- **ICD-10 Justification**: Automatic linking of diagnosis codes to procedures for proper billing justification

## Hospital Charge Form Features

The hospital.twig template implements a comprehensive hospital charge entry form with the following functional components:

### User Interface
- Responsive Bootstrap-based layout with custom styling
- Complete navigation integration with OpenEMR
- Success/error message handling after form submission

### Patient Management
- Autocomplete patient search with real-time API integration
- Patient selection with hidden ID field storage

### Diagnosis Code System
- ICD-10 diagnosis code search with autocomplete
- Support for up to 4 diagnosis codes
- Automatic display of code descriptions after selection
- Dynamic addition and removal of diagnosis rows

### Procedure/Charge Management
- CPT4 procedure code search with autocomplete
- Support for unlimited line items/charges (previous 6-item limit removed)
- Date picker for each service date entry
- Automatic price display after code selection
- Dynamic addition and removal of charge rows

### Billing Enhancements
- Support for up to 4 modifiers per procedure
- Diagnosis-procedure justification linking system
- Visual indicators for selected justifications
- CSRF protection for secure form submission

### Date and Print Handling
- Default current date pre-population
- jQuery date picker integration
- Integration with OpenEMR's print log system

## Installation

### Prerequisites

- OpenEMR 7.0.2 or compatible version
- PHP 8.1+ with Twig support
- Modern web browser with JavaScript enabled

### Installation Steps

1. **Copy Module Files**:
   Copy the entire `oe-module-hospital-charge` directory to your OpenEMR installation's `/interface/modules/custom_modules/` directory.

2. **Verify Dependencies**:
   Ensure that the following OpenEMR core file exists:
   - `/interface/forms/fee_sheet/review/views/review.css`

3. **Register the Module**:
   Enable the module through the OpenEMR module registration system if required.

4. **Set Permissions**:
   Ensure proper file permissions are set (typically directories: 755, files: 644).

## Usage

### Accessing the Module

1. Navigate to the Hospital Charge module from the OpenEMR menu.
2. The main interface displays the Rapid Charge Sheet.

### Adding Charges

1. **Select a Patient**:
   - Use the patient search field to find and select a patient.

2. **Add Charges Directly**:
   - Use the CPT/HCPCS search field to find and add procedure codes.
   - Use the ICD-10 search field to find and add diagnosis codes.
   - Each added code will appear in the charge table.

3. **Using the Review Panel**:
   - Click the "Review" button to open the charge review modal.
   - Select the desired encounter to view associated procedures and diagnoses.
   - Check the items you want to add to the charge sheet.
   - Click "Add" to transfer the selected items to the main form.

4. **Modify Charges**:
   - Adjust quantities, modifiers, and other fields as needed.
   - The total will update automatically.

5. **Save Changes**:
   - Click the "Save" button to save the charges to the patient's record.

## Technical Details

### Architecture

The module follows a Model-View-Controller (MVC) architecture:

- **Model**: PHP service classes in the `src/Service` directory
- **View**: Twig templates in the `templates` directory
- **Controller**: PHP controller classes in the `src/Controller` directory

### Key Components

- **ChargeReviewService**: Handles data retrieval for the review panel
- **ChargeReviewController**: Processes requests and renders the review interface
- **review.js**: Implements the Knockout.js view model for the review panel
- **home.twig**: Contains the main Rapid Charge Sheet interface

### Communication Flow

1. User clicks the "Review" button in home.twig
2. The review modal loads with patient data
3. User selects items and clicks "Add"
4. The review.js sends a message to the parent window using window.postMessage
5. The event listener in home.twig processes the message and adds the items to the form

## Customization

### Adding New Fields

To add new fields to the charge form:

1. Update the HTML table structure in `templates/home/home.twig`
2. Modify the `addRow` or `addCptRow` functions to include the new fields
3. Update the form submission handler to process the new fields

### Modifying the Review Panel

To customize the review panel:

1. Edit the Knockout.js templates in `templates/charge-review/review.twig`
2. Update the view model in `public/js/charge-review/review.js`
3. Modify the controller to provide any additional data needed

## Troubleshooting

### Common Issues

1. **Items not appearing in the charge sheet**:
   - Check browser console for JavaScript errors
   - Verify that the message event is being properly received
   - Ensure the table structure matches what the JavaScript functions expect

2. **Review modal not loading**:
   - Verify that the patient ID is being correctly passed
   - Check that the iframe URL is correct
   - Ensure the controller is returning the expected data

3. **Total not updating**:
   - Check that price inputs have the correct class ('price')
   - Verify that quantity inputs have the correct class ('units')
   - Ensure the updateTotal function is being called after adding items

## License

This module is released under the GNU General Public License v3.0 (GPL-3.0-only). See [LICENSE](LICENSE).

## Credits

Developed as a custom module for OpenEMR to streamline hospital charging processes.
