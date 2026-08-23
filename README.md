# KabanDesk

Internal IT ticketing and change management system for **Kaban Hotel and Casino Boracay**.

## Overview

KabanDesk is a PHP/MySQL helpdesk platform built to handle IT support requests across the property, with role-based dashboards, SLA tracking, and a built-in change management workflow.

## Features

- **Role-based dashboards** — End User, IT Support Specialist (Agent), Supervisor, and Admin views
- **Ticket management**
  - Auto-generated ticket numbers: `{CATEGORY_CODE}-{7 random alphanumeric}`
  - Pick Up Ticket modal for agents to claim incoming tickets
  - Security-hardened ticket detail page
- **SLA tracking** — `response_due_at` and `resolution_due_at` columns drive SLA compliance
- **Ticket categories**

  | Code | Category |
  |------|----------|
  | NET | Network |
  | POS | Point of Sale |
  | PMS | Property Management System |
  | CMS | Content Management System |
  | HW | Hardware |
  | SETUP | Setup / Provisioning |
  | SW | Software |
  | MAIL | Email |
  | CCTV | CCTV |
  | TEL | Telephony |
  | ACC | Account / Access |
  | FILE | File / Storage |
  | OTH | Other |

- **Change Management module**
  - Based on Kaban's Change Request Form and Change Management Policy
  - Change types: Standard, Normal, Major, Emergency
  - Approval matrix by change type (IT Personnel/Team Lead, IT Manager, IT Manager + Management, IT Manager/Authorized Personnel for emergencies)
  - 4-stage process: Request & Assessment → Approval & Planning → Implementation & Validation → Documentation & Closure

## Tech Stack
- **Frontend:** HTML5, CSS3, jQuery, MDBootstrap Pro, AJAX
- **Backend:** PHP, MySQL
- **Deployment:** Ubuntu LAMP server, Apache virtual hosts with reverse proxy

## Security

- SQL injection and IDOR hardening applied to ticket endpoints
- Output/header handling fixes (headers-already-sent issues resolved)

## Documentation

Full SDLC documentation is available, including:
- 9 UML diagrams (DFD, Use Case, Sequence, and others)
- HTML wireframes for the UI/UX

## Roles

| Role | `users.role` value |
|------|---------------------|
| End User | `End User` |
| Agent | `IT Support Specialist` |
| Supervisor | `Supervisor` |
| Admin | `Admin` |

## License

Internal use — Kaban Hotel and Casino Boracay
