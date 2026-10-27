<?php
// Edit these here only. register.php and api/register_user.php both use them.

const REGISTRATION_EMAIL_DOMAINS = ['kabanboracay.com'];   // staff email domain(s)

function registration_restricted_roles(): array {
    return ['IT Manager', 'Administrator', 'IT Supervisor',
            'IT Support Specialist', 'IT Helpdesk Support',
            'System Administrator', 'General Manager'];
}

// function registration_roles(): array {
//     $roles = json_decode(@file_get_contents(__DIR__ . '/../data/roles.json'), true);
//     if (!is_array($roles)) return [];
//     $roles = array_values(array_diff(array_map('trim', $roles), registration_restricted_roles()));
//     sort($roles);
//     return $roles;
// }

function registration_departments(): array {
    $d = ["Information Technology", "Human Resources", "Housekeeping", "Engineering",
          "Slot and Electronic Table Games", "Table Games", "Sales & Marketing", "Cage",
          "Surveillance", "Warehouse", "Gaming Security", "Finance & Accounting",
          "Building Management", "Front Office", "Food and Beverage", "Purchasing", "Upper Management"];
    sort($d);
    return $d;
}

?>