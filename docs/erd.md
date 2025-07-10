---
title: D'Agriventory Entity-Relationship Diagram
config:
  theme: neutral
---
erDiagram
    %% --------------------------------------------------------------------------------
    %% Comments on Changes from previous version:
    %% - employees table: Removed user_id FK. The employees table is now fully decoupled from the users table.
    %% - employees table: Added unique employee_number for a distinct identifier.
    %% - employees table: Added softDeletes for non-destructive removal.
    %% - employees table: Set division_id and position_id FKs to be nullable with onDelete('set null').
    %% - General: Added softDeletes to several tables to prevent permanent data loss.
    %% - General: Ensured all relationships and constraints match the latest migrations.
    %% --------------------------------------------------------------------------------

    users {
        bigint id PK
        varchar name
        varchar username UK
        varchar email UK
        varchar password
        varchar remember_token "nullable"
        timestamp email_verified_at "nullable"
        timestamp created_at
        timestamp updated_at
    }
    admin_users {
        bigint id PK
        bigint user_id FK
        varchar role "default: 'admin'"
        json permissions "nullable"
        boolean is_active "default: true"
        timestamp last_login_at "nullable"
        timestamp created_at
        timestamp updated_at
    }
    division_inventory_managers {
        bigint id PK
        bigint user_id FK
        bigint division_id FK, UK "One manager per division"
        timestamp created_at
        timestamp updated_at
    }
    employees {
        bigint id PK
        varchar name
        varchar employee_number UK
        bigint division_id FK "nullable"
        bigint position_id FK "nullable"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "nullable, for soft deletes"
    }
    divisions {
        bigint id PK
        varchar name UK
        varchar code UK
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "nullable, for soft deletes"
    }
    suppliers {
        bigint id PK
        varchar name UK
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "nullable, for soft deletes"
    }
    primary_categories {
        bigint id PK
        varchar name UK
        varchar code UK
        text description "nullable"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "nullable, for soft deletes"
    }
    secondary_categories {
        bigint id PK
        bigint primary_category_id FK
        varchar name UK
        varchar code UK
        text description "nullable"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "nullable, for soft deletes"
    }
    items_catalog {
        bigint id PK
        varchar name UK "Generic item name"
        varchar unit "Unit of measure"
        bigint secondary_category_id FK
        varchar code UK "Universal item code"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "nullable, for soft deletes"
    }
    positions {
        bigint id PK
        varchar title UK
        varchar code UK "nullable"
        enum position_type "DIVISION_CHIEF, COORDINATOR, etc."
        text description "nullable"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "nullable, for soft deletes"
    }
    contracts {
        bigint id PK
        bigint supplier_id FK
        varchar contract_po_ib_number UK
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "nullable, for soft deletes"
    }
    item_specifications {
        bigint id PK
        bigint item_catalog_id FK
        varchar brand "nullable"
        varchar model "nullable"
        text detailed_specifications "nullable"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "nullable, for soft deletes"
    }
    contract_items {
        bigint id PK
        bigint contract_id FK
        bigint item_specification_id FK
        decimal unit_price
        enum item_type "ICS, PAR, IDR"
        timestamp created_at
        timestamp updated_at
    }
    ics_number {
        bigint id PK
        varchar ics_number UK
        bigint assigned_employee_id FK
        bigint contract_item_id FK
        enum ics_type "SPLV, SPHV"
        int quantity
        int estimated_useful_life
        date date_prepared
        date date_accepted
        text remarks "nullable"
        timestamp created_at
        timestamp updated_at
    }
    ics_item_batches {
        bigint id PK
        bigint ics_number_id FK
        text identification_data "nullable, Serial numbers, etc."
        timestamp created_at
        timestamp updated_at
    }
    item_components {
        bigint id PK
        bigint ics_item_batch_id FK
        varchar component_type "e.g., Monitor, Casing, UPS"
        varchar brand "nullable"
        varchar model "nullable"
        varchar serial_number "nullable"
        timestamp created_at
        timestamp updated_at
    }
    ics_transfers {
        bigint id PK
        bigint ics_number_id FK
        bigint from_employee_id FK
        bigint to_employee_id FK
        date transfer_date
        timestamp created_at
        timestamp updated_at
    }
    par_number {
        bigint id PK
        varchar par_number UK
        bigint assigned_employee_id FK
        bigint contract_item_id FK
        int quantity
        varchar area_code
        varchar building_code
        varchar account_code
        date date_prepared
        date date_accepted
        text remarks "nullable"
        timestamp created_at
        timestamp updated_at
    }
    par_item_batches {
        bigint id PK
        bigint par_number_id FK
        text identification_data "nullable, Serial numbers, etc."
        timestamp created_at
        timestamp updated_at
    }
    par_transfers {
        bigint id PK
        bigint par_number_id FK
        bigint from_employee_id FK
        bigint to_employee_id FK
        date transfer_date
        timestamp created_at
        timestamp updated_at
    }
    idr_number {
        bigint id PK
        int number UK "Sequential IDR/RSMI number"
        bigint assigned_employee_id FK "Supply Officer"
        bigint approving_employee_id FK "Division Chief"
        bigint contract_item_id FK
        int quantity "Initial total quantity"
        varchar inventory_code
        varchar ors
        date date_prepared
        date date_accepted
        text remarks "nullable"
        timestamp created_at
        timestamp updated_at
    }
    idr_item_batches {
        bigint id PK
        bigint idr_number_id FK
        text identification_data "nullable, Serial numbers, etc."
        timestamp created_at
        timestamp updated_at
    }
    acknowledgement_receipts {
        bigint id PK
        bigint idr_item_batch_id FK
        int quantity_reduced
        timestamp created_at
        timestamp updated_at
    }
    consumable_records {
        bigint id PK
        varchar record_number UK
        bigint division_id FK
        date date_received
        text remarks "nullable"
        timestamp created_at
        timestamp updated_at
    }
    consumable_items {
        bigint id PK
        bigint consumable_record_id FK
        bigint item_specification_id FK
        int initial_quantity
        int current_quantity
        timestamp created_at
        timestamp updated_at
    }
    audit_logs {
        bigint id PK
        bigint user_id FK "nullable"
        varchar table_name
        bigint record_id
        varchar action_type "e.g., 'CREATE', 'UPDATE', 'DELETE'"
        json old_values "nullable"
        json new_values "nullable"
        text description "nullable"
        timestamp created_at
    }

    users ||--o| admin_users : "is an"
    users ||--o| division_inventory_managers : "can be a"
    divisions ||--o| division_inventory_managers : "is managed by"
    divisions ||--o{ employees : "employs"
    positions ||--o{ employees : "defines role for"
    primary_categories ||--o{ secondary_categories : "contains"
    secondary_categories ||--o{ items_catalog : "categorizes"
    items_catalog ||--o{ item_specifications : "has variants"
    suppliers ||--o{ contracts : "supplies"
    contracts ||--o{ contract_items : "contains"
    item_specifications ||--o{ contract_items : "specified in"
    employees ||--o{ ics_number : "assigned"
    contract_items ||--o{ ics_number : "sourced from"
    ics_number ||--o{ ics_item_batches : "contains"
    ics_item_batches ||--o{ item_components : "has"
    ics_number ||--o{ ics_transfers : "is transferred via"
    employees ||--o{ ics_transfers : "is from"
    employees ||--o{ ics_transfers : "is to"
    employees ||--o{ par_number : "assigned"
    contract_items ||--o{ par_number : "sourced from"
    par_number ||--o{ par_item_batches : "contains"
    par_number ||--o{ par_transfers : "is transferred via"
    employees ||--o{ par_transfers : "is from"
    employees ||--o{ par_transfers : "is to"
    employees ||--o{ idr_number : "is assigned to"
    employees ||--o{ idr_number : "is approved by"
    contract_items ||--o{ idr_number : "sourced from"
    idr_number ||--o{ idr_item_batches : "contains"
    idr_item_batches ||--o{ acknowledgement_receipts : "is drawn down by"
    divisions ||--o{ consumable_records : "owns"
    consumable_records ||--o{ consumable_items : "details"
    item_specifications ||--o{ consumable_items : "specifies"
    users ||--o{ audit_logs : "performed action" 