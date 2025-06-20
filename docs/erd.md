```mermaid

---
config:
  theme: redux-dark-color
---
erDiagram
    users {
        bigint id PK " // mandatory"
        varchar name " // mandatory"
        varchar email UK " // mandatory"
        varchar password " // mandatory"
        varchar remember_token " // nullable"
        timestamp email_verified_at " // nullable"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    admin_users {
        bigint id PK " // mandatory"
        bigint user_id FK " // mandatory"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    division_inventory_managers {
        bigint id PK " // mandatory"
        bigint user_id FK " // mandatory"
        bigint division_id FK, UK "The division this user manages. One manager per division. // mandatory"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    employees {
        bigint id PK " // mandatory"
        varchar name " // mandatory"
        bigint division_id FK "The division this employee belongs to. // nullable"
        bigint position_id FK "The specific position/role. // nullable"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    divisions {
        bigint id PK " // mandatory"
        varchar name UK "Name of the division or office. // mandatory"
        varchar code UK "Unique code for the division. // mandatory"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    suppliers {
        bigint id PK " // mandatory"
        varchar name UK " // mandatory"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    primary_categories {
        bigint id PK " // mandatory"
        varchar name UK " // mandatory"
        varchar code UK " // mandatory"
        text description " // nullable"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    secondary_categories {
        bigint id PK " // mandatory"
        bigint primary_category_id FK " // mandatory"
        varchar name UK " // mandatory"
        varchar code UK " // mandatory"
        text description " // nullable"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    items_catalog {
        bigint id PK " // mandatory"
        varchar name UK "Generic item name. // mandatory"
        varchar unit "unit of measure. // mandatory"
        bigint secondary_category_id FK " // mandatory"
        varchar code UK "Universal item code. // mandatory"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    positions {
        bigint id PK " // mandatory"
        varchar title UK "Position title: Chief Administrative Officer, HRMDS Chief, Rice Coordinator, HVCDP Coordinator, SAAD Operations Officer, etc. // mandatory"
        varchar code UK "Position code/abbreviation. // nullable"
        enum position_type "DIVISION_CHIEF, COORDINATOR, FOCAL_PERSON, OFFICER, SPECIALIST, OTHER. // mandatory"
        text description "Position description and responsibilities. // nullable"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    contracts {
        bigint id PK " // mandatory"
        bigint supplier_id FK " // mandatory"
        varchar contract_po_ib_number UK " // mandatory"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    item_specifications {
        bigint id PK " // mandatory"
        bigint item_catalog_id FK " // mandatory"
        varchar brand " // nullable"
        varchar model " // nullable"
        text detailed_specifications " // nullable"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    contract_items {
        bigint id PK " // mandatory"
        bigint contract_id FK " // mandatory"
        bigint item_specification_id FK " // mandatory"
        decimal unit_price " // mandatory"
        enum item_type "ICS, PAR, IDR. // mandatory"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    ics_number {
        bigint id PK " // mandatory"
        bigint assigned_employee_id FK " // mandatory"
        bigint contract_item_id FK " // mandatory"
        enum ics_type "SPLV, SPHV. // mandatory"
        int estimated_useful_life "ICS specific field. // mandatory"
        date date_accepted " // mandatory"
        text remarks " // nullable"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    ics_item_batches {
        bigint id PK " // mandatory"
        bigint ics_number_id FK " // mandatory"
        int quantity "Quantity for this batch. // mandatory"
        text identification_data "Serial numbers, asset tags. // nullable"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    ics_transfers {
        bigint id PK " // mandatory"
        bigint ics_number_id FK " // mandatory"
        bigint from_employee_id FK " // mandatory"
        bigint to_employee_id FK " // mandatory"
        date transfer_date " // mandatory"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    par_number {
        bigint id PK " // mandatory"
        bigint assigned_employee_id FK " // mandatory"
        bigint contract_item_id FK " // mandatory"
        varchar area_code "PAR specific field. // mandatory"
        varchar building_code "PAR specific field. // mandatory"
        varchar account_code "PAR specific field. // mandatory"
        date date_accepted " // mandatory"
        text remarks " // nullable"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    par_item_batches {
        bigint id PK " // mandatory"
        bigint par_number_id FK " // mandatory"
        int quantity "Quantity for this batch. // mandatory"
        text identification_data "Serial numbers, asset tags. // nullable"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    par_transfers {
        bigint id PK " // mandatory"
        bigint par_number_id FK " // mandatory"
        bigint from_employee_id FK " // mandatory"
        bigint to_employee_id FK " // mandatory"
        date transfer_date " // mandatory"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    idr_number {
        bigint id PK " // mandatory"
        int number UK "Sequential IDR/RSMI number. // mandatory"
        bigint assigned_employee_id FK "The employee responsible for the stock (e.g. Supply Officer). // mandatory"
        bigint approving_employee_id FK "The division chief who approves this IDR. // mandatory"
        bigint contract_item_id FK " // mandatory"
        varchar inventory_code "IDR specific field. // mandatory"
        varchar ors "IDR specific field. // mandatory"
        date date_accepted " // mandatory"
        text remarks " // nullable"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    idr_item_batches {
        bigint id PK " // mandatory"
        bigint idr_number_id FK " // mandatory"
        int quantity "The initial total quantity for this batch/card. // mandatory"
        text identification_data "Serial numbers, asset tags. // nullable"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    acknowledgement_receipts {
        bigint id PK " // mandatory"
        bigint idr_item_batch_id FK "The batch this AR draws from. // mandatory"
        int quantity_reduced "Quantity taken/reduced in this transaction. // mandatory"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    consumable_records {
        bigint id PK " // mandatory"
        varchar record_number UK "Unique record number for this batch. // mandatory"
        bigint division_id FK "The division that owns this stock. // mandatory"
        date date_received " // mandatory"
        text remarks " // nullable"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    consumable_items {
        bigint id PK " // mandatory"
        bigint consumable_record_id FK " // mandatory"
        bigint item_specification_id FK " // mandatory"
        int initial_quantity " // mandatory"
        int current_quantity "Updated by the division inventory manager. // mandatory"
        timestamp created_at " // mandatory"
        timestamp updated_at " // mandatory"
    }
    audit_logs {
        bigint id PK " // mandatory"
        bigint user_id FK "The user who performed the action. // nullable"
        varchar table_name "The table where the action occurred (e.g., 'users', 'items_catalog'). // mandatory"
        bigint record_id "The ID of the record in the 'table_name' that was affected. // mandatory"
        varchar action_type "e.g., 'CREATE', 'UPDATE', 'DELETE'. // mandatory"
        json old_values "JSON blob of the record's state before the change (for UPDATE/DELETE). // nullable"
        json new_values "JSON blob of the record's state after the change (for CREATE/UPDATE). // nullable"
        text description "Optional: A brief description or reason for the action. // nullable"
        timestamp created_at "Timestamp of the log entry. // mandatory"
    }

    users ||--|| admin_users : "is_an"
    users ||--|| division_inventory_managers : "can_be_a"
    divisions ||--o| division_inventory_managers : "is_managed_by"
    divisions ||--o{ employees : "employs"
    positions ||--o{ employees : "defines_role_for"
    primary_categories ||--o{ secondary_categories : "contains"
    secondary_categories ||--o{ items_catalog : "categorizes"
    items_catalog ||--o{ item_specifications : "has_variants"
    suppliers ||--o{ contracts : "supplies"
    contracts ||--o{ contract_items : "contains"
    item_specifications ||--o{ contract_items : "specified_in"
    employees ||--o{ ics_number : "assigned_ics"
    contract_items ||--o{ ics_number : "sourced_from_ics"
    ics_number ||--o{ ics_item_batches : "contains_ics_batches"
    ics_number ||--o{ ics_transfers : "transferred_via_ics"
    employees ||--o{ ics_transfers : "ics_from_employee"
    employees ||--o{ ics_transfers : "ics_to_employee"
    employees ||--o{ par_number : "assigned_par"
    contract_items ||--o{ par_number : "sourced_from_par"
    par_number ||--o{ par_item_batches : "contains_par_batches"
    par_number ||--o{ par_transfers : "transferred_via_par"
    employees ||--o{ par_transfers : "par_from_employee"
    employees ||--o{ par_transfers : "par_to_employee"
    employees ||--o{ idr_number : "is_assigned_to"
    employees ||--o{ idr_number : "approves_idr"
    contract_items ||--o{ idr_number : "sourced_from_idr"
    idr_number ||--o{ idr_item_batches : "contains_idr_batches"
    idr_item_batches ||--o{ acknowledgement_receipts : "is_drawn_down_by"
    divisions ||--o{ consumable_records : "owns"
    consumable_records ||--o{ consumable_items : "details"
    item_specifications ||--o{ consumable_items : "specifies"
    users ||--o{ audit_logs : "performed_action"