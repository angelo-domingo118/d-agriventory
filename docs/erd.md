erDiagram
    users {
        bigint id PK
        varchar name
        varchar email UK
        varchar password
        varchar remember_token
        timestamp email_verified_at
        timestamp created_at
        timestamp updated_at
    }

    admin_users {
        bigint id PK
        bigint user_id FK
        timestamp created_at
        timestamp updated_at
    }

    clerk_users {
        bigint id PK
        bigint user_id FK
        bigint division_id FK
        timestamp created_at
        timestamp updated_at
    }

    divisions {
        bigint id PK
        varchar name
        varchar code
        varchar description
        timestamp created_at
        timestamp updated_at
    }

    employees {
        bigint id PK
        varchar name
        timestamp created_at
        timestamp updated_at
    }

    suppliers {
        bigint id PK
        varchar name UK
        varchar contact_info
        timestamp created_at
        timestamp updated_at
    }

    articles {
        bigint id PK
        varchar name UK
        varchar unit
        enum category "consumable, par, ics, idr"
        enum status "active, inactive"
        timestamp created_at
        timestamp updated_at
    }

    article_descriptions {
        bigint id PK
        bigint article_id FK
        varchar brand
        varchar model
        text description
        decimal unit_cost
        timestamp created_at
        timestamp updated_at
    }

    consumables {
        bigint id PK
        bigint article_description_id FK
        bigint division_id FK
        int quantity_on_hand
        int reorder_level
        int max_stock_level
        timestamp last_updated
        timestamp created_at
        timestamp updated_at
    }

    par_groups {
        bigint id PK
        varchar par_number UK "PAR Number"
        bigint article_description_id FK
        bigint assigned_employee_id FK
        date date_prepared
        int total_quantity
        varchar unit_measure
        int year_acquired
        varchar account_code
        varchar area_code
        varchar building_code
        date date_accepted
        bigint supplier_id FK
        varchar contract_po_ib_number
        text remarks
        varchar issued_to
        varchar issued_to_position
        varchar re_issued_to
        varchar re_issued_to_position
        timestamp created_at
        timestamp updated_at
    }

    par_items {
        bigint id PK
        bigint par_group_id FK
        varchar series_number UK
        varchar old_property_number
        enum item_status "active, transferred, disposed"
        timestamp created_at
        timestamp updated_at
    }

    ics_groups {
        bigint id PK
        varchar ics_number UK "ICS Number"
        bigint article_description_id FK
        bigint assigned_employee_id FK
        date date_prepared
        int total_quantity
        varchar unit_measure
        enum ics_type "sphv, splv"
        varchar month_acquired
        int year
        int estimated_useful_life
        date date_accepted
        bigint supplier_id FK
        varchar contract_po_ib_number
        text remarks
        varchar issued_to
        varchar issued_to_position
        varchar re_issued_to
        varchar re_issued_to_position
        timestamp created_at
        timestamp updated_at
    }

    ics_items {
        bigint id PK
        bigint ics_group_id FK
        varchar series_number UK
        varchar old_property_number
        enum item_status "active, transferred, disposed"
        timestamp created_at
        timestamp updated_at
    }

    idr_groups {
        bigint id PK
        varchar rsmi_number UK "RSMI Number"
        bigint article_description_id FK
        bigint assigned_employee_id FK
        date date_prepared
        int total_quantity
        varchar unit_measure
        varchar inventory_code
        int year_acquired
        varchar location_code
        date date_accepted
        bigint supplier_id FK
        varchar contract_po_ib_number
        varchar ors_number
        varchar issued_to
        varchar issued_to_position
        int balance_per_card
        varchar ar_submitted_1
        varchar ar_submitted_2
        varchar ar_submitted_3
        varchar ar_submitted_4
        varchar latest_ar
        text remarks
        varchar item_no
        varchar division_chief
        varchar division_position
        timestamp created_at
        timestamp updated_at
    }

    idr_items {
        bigint id PK
        bigint idr_group_id FK
        varchar series_number UK
        enum item_status "active, transferred, disposed"
        timestamp created_at
        timestamp updated_at
    }

    inventory_transactions {
        bigint id PK
        bigint article_description_id FK
        bigint admin_user_id FK
        enum transaction_type "stock_in, stock_out, adjustment, transfer"
        int quantity
        decimal unit_cost
        text reference_number
        text remarks
        timestamp transaction_date
        timestamp created_at
        timestamp updated_at
    }

    admin_sessions {
        varchar id PK
        bigint admin_user_id FK
        varchar ip_address
        text user_agent
        longtext payload
        int last_activity
    }

    clerk_sessions {
        varchar id PK
        bigint clerk_user_id FK
        varchar ip_address
        text user_agent
        longtext payload
        int last_activity
    }

    password_reset_tokens {
        varchar email PK
        varchar token
        timestamp created_at
    }

    %% Base User Relationships
    users ||--|| admin_users : "extends"
    users ||--|| clerk_users : "extends"
    users ||--|| password_reset_tokens : "has"

    %% User Management Relationships
    admin_users ||--o{ admin_sessions : "has"
    clerk_users ||--o{ clerk_sessions : "has"

    %% Division Relationships
    divisions ||--|| clerk_users : "has_one_clerk"
    divisions ||--o{ consumables : "contains"

    %% Article Structure Relationships
    articles ||--o{ article_descriptions : "has_variants"
    suppliers ||--o{ par_groups : "supplies"
    suppliers ||--o{ ics_groups : "supplies"
    suppliers ||--o{ idr_groups : "supplies"

    %% Article Description Relationships
    article_descriptions ||--o{ consumables : "stocked_as"
    article_descriptions ||--o{ par_groups : "defined_as"
    article_descriptions ||--o{ ics_groups : "defined_as"
    article_descriptions ||--o{ idr_groups : "defined_as"

    %% Group to Individual Item Relationships
    par_groups ||--o{ par_items : "contains"
    ics_groups ||--o{ ics_items : "contains"
    idr_groups ||--o{ idr_items : "contains"

    %% Employee Assignment Relationships
    employees ||--o{ par_groups : "assigned_to"
    employees ||--o{ ics_groups : "assigned_to"
    employees ||--o{ idr_groups : "assigned_to"

    %% Admin Access to Core Tables
    admin_users ||--o{ articles : "manages"
    admin_users ||--o{ employees : "manages"
    admin_users ||--o{ par_groups : "manages"
    admin_users ||--o{ ics_groups : "manages"
    admin_users ||--o{ idr_groups : "manages"

    %% Transaction Relationships
    article_descriptions ||--o{ inventory_transactions : "tracks"
    admin_users ||--o{ inventory_transactions : "records"

    %% Division-Consumable Relationship
    clerk_users ||--o{ consumables : "manages"