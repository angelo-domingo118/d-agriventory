# PAR Seeding Guide

This document explains how to use the new PAR seeders to migrate legacy PAR data from `par-seeder.json` into the D'Agriventory database schema.

## Overview

The PAR seeding process migrates legacy PAR (Property Acknowledgment Receipt) data from a JSON file format to the current database schema. The process handles:

- Creating missing suppliers and contracts
- Matching/creating employees with proper name formatting
- Creating item catalog entries and specifications
- Generating PAR numbers with proper relationships
- Creating PAR item batches with identification data

## Files Created

1. **`ParNumberSeeder.php`** - Main seeder for PAR number records
2. **`ParItemBatchSeeder.php`** - Seeder for PAR item batches with identification data
3. **Updated `DatabaseSeeder.php`** - Includes the new PAR seeders

## Data Mapping

### Legacy JSON Format → Database Schema

| JSON Field | Database Table/Field | Notes |
|------------|---------------------|-------|
| PAR Number | `par_number.par_number` | Direct mapping |
| Issued To | `par_number.assigned_employee_id` | Employee lookup/creation |
| Document Source | `contracts.contract_po_ib_number` + `suppliers.name` | Parsed from format: "Supplier: Name ; Contract/PO/IB No: Number" |
| Article | `items_catalog.name` | Item catalog lookup/creation |
| Description | `item_specifications.*` | Parsed for brand, model, detailed specs |
| Unit Cost | `contract_items.unit_price` | Price parsing (removes commas) |
| Quantity | `par_number.quantity` | Direct mapping |
| Unit Measure | `items_catalog.unit` | Normalized to lowercase |
| Area Code | `par_number.area_code` | Direct mapping |
| Building Code | `par_number.building_code` | Direct mapping |
| Account Code | `par_number.account_code` | Direct mapping |
| Date Prepared | `par_number.date_prepared` | Date parsing (multiple formats) |
| Date Accepted | `par_number.date_accepted` | Date parsing (multiple formats) |
| Year Acquired | `par_number.date_acquired` | Uses January 1st as default |
| Remarks | `par_number.remarks` | Direct mapping |
| N/A | `par_number.inventory_code` | Generated: "PAR-{account_code}-{article_abbrev}-{unique_id}" |

### Identification Data Extraction

The `ParItemBatchSeeder` extracts identification data from the description field:

- **Serial Numbers**: Extracted from "Serial Number: ..." patterns
- **Ear Tags**: For livestock (sheep, goats) from "ear tag: ..." patterns
- **Asset Tags**: From "Asset Tag: ..." patterns
- **Component Data**: For complex items like desktop computers

## Prerequisites

1. **File Location**: Ensure `par-seeder.json` is placed in the project root directory
2. **Database Schema**: Run all migrations first
3. **Base Data**: Ensure the following seeders have run:
   - `AdminUserSeeder`
   - `UserSeeder`
   - `DivisionSeeder`
   - `EmployeeSeeder`
   - `PrimaryCategorySeeder`
   - `SecondaryCategorySeeder`
   - `ItemsCatalogSeeder`
   - `ItemSpecificationSeeder`
   - `SupplierAndContractSeeder`
   - `ContractItemsSeeder`

## Running the Seeders

### Option 1: Run All Seeders (Recommended)
```bash
php artisan db:seed
```

### Option 2: Run PAR Seeders Only
```bash
php artisan db:seed --class=ParNumberSeeder
php artisan db:seed --class=ParItemBatchSeeder
```

### Option 3: Run Individual Seeders
```bash
# First run the PAR number seeder
php artisan db:seed --class=ParNumberSeeder

# Then run the PAR item batch seeder
php artisan db:seed --class=ParItemBatchSeeder
```

## Error Handling

The seeders include comprehensive error handling:

### ParNumberSeeder Errors
- **Missing JSON file**: Stops execution with error message
- **Invalid JSON data**: Stops execution with error message
- **Duplicate PAR numbers**: Skips record with warning
- **Employee not found**: Creates new employee with warning
- **Date parsing errors**: Uses current date with warning
- **Supplier/contract parsing errors**: Skips record with error

### ParItemBatchSeeder Errors
- **Missing PAR number record**: Skips batch with error
- **Duplicate batches**: Skips batch with warning

## Data Validation

After running the seeders, verify the data:

### Check PAR Numbers
```sql
SELECT COUNT(*) FROM par_number;
SELECT par_number, assigned_employee_id, contract_item_id FROM par_number LIMIT 10;
```

### Check PAR Item Batches
```sql
SELECT COUNT(*) FROM par_item_batches;
SELECT par_number_id, identification_data FROM par_item_batches WHERE identification_data IS NOT NULL LIMIT 10;
```

### Check Relationships
```sql
-- Check PAR numbers with their employees
SELECT p.par_number, e.name 
FROM par_number p 
JOIN employees e ON p.assigned_employee_id = e.id 
LIMIT 10;

-- Check PAR numbers with their contract items
SELECT p.par_number, ci.unit_price, is.brand, is.model, ic.name
FROM par_number p 
JOIN contract_items ci ON p.contract_item_id = ci.id
JOIN item_specifications is ON ci.item_specification_id = is.id
JOIN items_catalog ic ON is.item_catalog_id = ic.id
LIMIT 10;
```

## Performance Notes

- **Chunked Processing**: Both seeders process data in chunks of 50 records to avoid memory issues
- **Transaction Wrapping**: Each chunk is wrapped in a database transaction for consistency
- **Progress Reporting**: Console output shows progress and any warnings/errors

## Troubleshooting

### Common Issues

1. **"PAR seeder JSON file not found"**
   - Ensure `par-seeder.json` is in the project root directory
   - Check file permissions

2. **"PAR number X already exists"**
   - PAR numbers must be unique
   - Check for duplicate data in the JSON file
   - Clear existing PAR data if re-seeding

3. **"Could not parse supplier/contract from document source"**
   - Check the format of the "Document Source" field
   - Expected format: "Supplier: Name ; Contract/PO/IB No: Number"

4. **Memory issues with large datasets**
   - The seeders process in chunks of 50 records
   - For very large datasets, consider reducing chunk size in the seeder code

### Re-seeding

To re-seed PAR data:

```bash
# Clear existing PAR data
php artisan db:seed --class=ClearParDataSeeder  # You may need to create this

# Or truncate tables manually (be careful!)
# TRUNCATE TABLE par_item_batches;
# TRUNCATE TABLE par_number;

# Then re-run the seeders
php artisan db:seed --class=ParNumberSeeder
php artisan db:seed --class=ParItemBatchSeeder
```

## Expected Results

After successful seeding, you should have:

- **PAR Numbers**: All records from the JSON file converted to database records
- **PAR Item Batches**: One batch per PAR number with extracted identification data
- **New Employees**: Any employees not previously in the system
- **New Suppliers/Contracts**: Any suppliers/contracts not previously in the system
- **New Items**: Any items not previously in the catalog

The seeders will report the number of processed and skipped records, helping you verify the migration was successful.
