# 🌀 AnimaID — Functional Architecture Document

---

## 📘 1. Introduction

**AnimaID** is a management and coordination platform for **animation centers**, **educational labs**, and **recreational activities**.
Its goal is to provide a **unified digital environment** to coordinate staff, organize activities, manage attendance, registrations, and communications, while also offering a public window accessible to parents and families.

The system is designed to be:

* **Scalable** and easily adaptable to multiple centers (multi-deployment)
* **Extensible** through a modular *Applets* system
* **Accessible** from web and mobile devices
* **Containerizable and portable** in cloud or on-premise environments

---

## 🎯 2. Vision and Objectives

### Main Objectives

1. Digitize the management of animation centers.
2. Provide a single interface for coordinators, animators, and managers.
3. Offer parents simple and immediate access to information, calendars, and registrations.
4. Create a modular, extensible, and customizable ecosystem.

### Secondary Objectives

* Promote collaboration and sharing of best practices.
* Centralize media, documents, and content.
* Automate operational processes (notifications, reports, statistics).
* Provide analysis tools to optimize resources and shifts.

---

## 👥 3. User Types

### **1. Technical Admins**

Manage the platform at a systemic level:

* Creation and configuration of the center instance.
* Role management, permissions, backups, and updates.
* Supervision and system diagnostics.

### **2. AnimaID Staff**

Operational center personnel, organized in levels of responsibility:

* `@organizzatore`
* `@responsabile`
* `@animatore`
* `@aiutoanimatore`

Each level has **cumulative tags**, which determine progressive access to modules.

### **3. Parents / External Users**

Do not require permanent login.
Can:

* Consult the public calendar.
* Register their children.
* View approved communications and media.

---

## 🧩 4. Functional Architecture

AnimaID is built on **functional modules** (system core) that cooperate through internal APIs.
Each module represents an autonomous functional domain, but integrated into the overall flow.

### **Main Modules List**

* **Registrations and Records**
* **Public and Operational Calendar**
* **Attendance and Shifts**
* **Communications (internal and public)**
* **Wiki / Games Database**
* **Media & Document Explorer**
* **Space Booking and Map**
* **Reporting and KPIs**
* **Role and Permission Management**
* **Center Configuration and Setup**

---

## 🔄 5. User Flows

UML diagram showing interactions between the three user categories and main modules.

```plantuml
@startuml
title User Flows - AnimaID

actor "Technical Admin" as Admin
actor "AnimaID Staff" as Staff
actor "Parent / External User" as Parent

rectangle "AnimaID Platform" {

    package "Management and Configuration" {
        usecase "Center Setup\n(Instance Creation, Config, Backup)" as U1
        usecase "Role and Permission Management\n(Cumulative Tags)" as U2
    }

    package "Staff Operational Area" {
        usecase "Registration Management\n(Validation, Records)" as U3
        usecase "Operational Calendar\n(Shifts, Activities, Events)" as U4
        usecase "Staff Attendance and Shifts\n(Check-in/Out, Statistics)" as U5
        usecase "Internal Communications\n(Notice Board, Chat, Notifications)" as U6
        usecase "Wiki / Games Database\n(Search, Insertion, Feedback)" as U7
        usecase "Media Management\n(Upload, Classification, Sharing)" as U8
        usecase "Space Booking\n(Interactive Map)" as U9
    }

    package "Public Area" {
        usecase "Online Registration\n(Public Module)" as U10
        usecase "Public Calendar\n(Events, Notices)" as U11
        usecase "Media Consultation\n(Photos, Videos, Documents)" as U12
        usecase "View Communications\n(News, General Notices)" as U13
    }
}

Admin --> U1
Admin --> U2

Staff --> U3
Staff --> U4
Staff --> U5
Staff --> U6
Staff --> U7
Staff --> U8
Staff --> U9

Parent --> U10
Parent --> U11
Parent --> U12
Parent --> U13

U10 --> U3 : "Registration Request"
U3 --> U5 : "Child validated\nenters attendance system"
U4 --> U9 : "Assign space"
U4 --> U5 : "Shifts associated with attendance"
U6 --> U8 : "Share media content"
U8 --> U12 : "Publish approved media"
U6 --> U13 : "Publish public communications"

@enduml
```

---

## 🗺️ 6. Functional Map

Representation of modules and their logical interconnections.

```plantuml
@startuml
title Functional Map - AnimaID

skinparam rectangle {
  BackgroundColor<<Public>> #E6F4EA
  BackgroundColor<<Staff>> #E9F0FA
  BackgroundColor<<Admin>> #FBEAD7
  BackgroundColor<<Core>> #F2F2F2
  BorderColor #AAAAAA
  RoundCorner 15
}

rectangle "AnimaID Platform" as AnimaID {

  rectangle "System Core" <<Core>> {
    [Authentication & Tags\n(Access Management, Levels, Login API)]
    [Central Database\n(Users, Children, Activities, Documents)]
    [Unified API\n(Rest/GraphQL for Web and Mobile)]
  }

  rectangle "Public Area" <<Public>> {
    [Public Registration Module\n(Forms, Consents, Documents)]
    [Public Calendar\n(Events, Notices, Days)]
    [News Board\n(External Communications)]
    [Media Gallery\n(Photos, Videos, Shared Documents)]
  }

  rectangle "Staff Operational Area" <<Staff>> {
    [Registration Management\n(Validation, Records, Health Data)]
    [Operational Calendar\n(Shifts, Activities, Availability)]
    [Attendance Management\n(Check-in, Statistics, Reports)]
    [Internal Communications\n(Chat, Notice Board, Notifications)]
    [Wiki / Games Database\n(Activity Cards, Filters, Feedback)]
    [Media Management\n(Upload, Approval, Archiving)]
    [Space Booking\n(Map, Shared Resources)]
  }

  rectangle "Administrative Area" <<Admin>> {
    [Center Setup\n(Instance Creation, Configuration)]
    [Role and Permission Management\n(Cumulative Tags and Policies)]
    [Backup, Updates, Integrations]
    [Advanced Reporting\n(KPIs, Exports, Statistics)]
  }
}

[Public Registration Module] --> [Registration Management]
[Registration Management] --> [Attendance Management]
[Operational Calendar] --> [Attendance Management]
[Operational Calendar] --> [Space Booking]
[Internal Communications] --> [Wiki / Games Database]
[Media Management] --> [Media Gallery]
[News Board] --> [Public Calendar]

[Center Setup] --> [Role and Permission Management]
[Role and Permission Management] --> [Authentication & Tags]
[Advanced Reporting] --> [Attendance Management]
[Advanced Reporting] --> [Operational Calendar]

@enduml
```

---

## 💻 7. Interface Map

Representation of user interfaces and the contact point between users and system.

```plantuml
@startuml
title Interface Map - AnimaID

skinparam rectangle {
  BackgroundColor<<UI>> #E8F0FE
  BackgroundColor<<Public>> #E6F4EA
  BackgroundColor<<Mobile>> #FFF4E6
  BackgroundColor<<Admin>> #FBEAD7
  BackgroundColor<<API>> #F2F2F2
  BorderColor #AAAAAA
  RoundCorner 15
}

actor "Technical Admin" as Admin
actor "AnimaID Staff" as Staff
actor "Parent / External User" as Parent

rectangle "AnimaID Interfaces" {

  rectangle "Administrative Console (Web)" <<Admin>> {
    [Configuration Dashboard]
    [System Monitoring]
  }

  rectangle "Staff Web App" <<UI>> {
    [Daily Dashboard]
    [Registration Management]
    [Operational Calendar]
    [Wiki / Games Database]
    [Media Management]
    [Internal Communications]
    [Space Booking]
  }

  rectangle "Staff Mobile App (Android)" <<Mobile>> {
    [Check-in/Check-out Attendance]
    [Internal Chat / Push Notifications]
    [Activity Consultation]
    [Photo/Video Upload]
  }

  rectangle "Public Portal (Web)" <<Public>> {
    [Online Registration Module]
    [Public Events Calendar]
    [News Board]
    [Media Gallery]
  }

  rectangle "API Gateway" <<API>> {
    [Authentication / Tag API]
    [Operational Data API]
    [Media / Documents API]
    [Notifications API]
  }
}

Admin --> "Administrative Console (Web)"
Staff --> "Staff Web App"
Staff --> "Staff Mobile App (Android)"
Parent --> "Public Portal (Web)"

"Administrative Console (Web)" --> "API Gateway"
"Staff Web App" --> "API Gateway"
"Staff Mobile App (Android)" --> "API Gateway"
"Public Portal (Web)" --> "API Gateway"

@enduml
```

---

## 🧱 8. Two-Layer Architecture: Modules and Applets

```plantuml
@startuml
title Two-Layer Functional Architecture - AnimaID

skinparam rectangle {
  BackgroundColor<<Module>> #E9F0FA
  BackgroundColor<<Applet>> #E6F4EA
  BackgroundColor<<Manager>> #FFF3E6
  BorderColor #AAAAAA
  RoundCorner 15
}

rectangle "Base Layer - Core Modules" {
  [Registrations] <<Module>>
  [Calendar] <<Module>>
  [Attendance] <<Module>>
  [Communications] <<Module>>
  [Wiki / Games] <<Module>>
  [Media Manager] <<Module>>
  [Space Booking] <<Module>>
  [Authentication & Roles] <<Module>>
  [Reporting] <<Module>>
}

rectangle "Upper Layer - Applets" {
  [Advanced Planner] <<Applet>>
  [Child Card+] <<Applet>>
  [Digital Signatures Module] <<Applet>>
  [Interactive 3D Map] <<Applet>>
  [Photo Day Manager] <<Applet>>
  [Feedback Center] <<Applet>>
  [Event Trigger Bot] <<Applet>>
}

rectangle "Applet Catalog / Extension Manager" <<Manager>> {
  [Applets Catalog\nManagement, Versions, Permissions]
}

[Applets Catalog\nManagement, Versions, Permissions] --> [Advanced Planner]
[Applets Catalog\nManagement, Versions, Permissions] --> [Child Card+]
[Applets Catalog\nManagement, Versions, Permissions] --> [Digital Signatures Module]
[Applets Catalog\nManagement, Versions, Permissions] --> [Feedback Center]
[Applets Catalog\nManagement, Versions, Permissions] --> [Photo Day Manager]

[Advanced Planner] --> [Calendar]
[Advanced Planner] --> [Attendance]
[Child Card+] --> [Registrations]
[Child Card+] --> [Media Manager]
[Digital Signatures Module] --> [Registrations]
[Interactive 3D Map] --> [Space Booking]
[Photo Day Manager] --> [Media Manager]
[Feedback Center] --> [Communications]
[Event Trigger Bot] --> [Communications]
[Event Trigger Bot] --> [Calendar]
[Event Trigger Bot] --> [Attendance]

@enduml
```

---

## ⚙️ 9. Applets Catalog and Extension Logics

### Example Manifest

```yaml
id: applet.feedback_center
name: Feedback Center
description: Collection of feedback from parents and staff
dependencies:
  - module: communications
  - module: wiki
permissions:
  - read: communications
  - write: feedback
entrypoint: /applets/feedback
```

### Key Features

* Centralized management in the **Applets Catalog**
* Each applet defines **dependencies on modules** and **required permissions**
* **Hot-pluggable system**: applets can be activated/deactivated without interrupting service
* Possibility of **external catalog (marketplace)** for distribution

---

## 🛠️ 9.5. Tech Stack

### Core Technologies

* **Database**: SQLite - Lightweight, file-based database suitable for multi-deployment and portability
* **Backend**: PHP - Server-side scripting for business logic, API endpoints, and module interactions
* **Frontend**: HTML5, TailwindCSS, FontAwesome - Responsive web interfaces with utility-first CSS and icon library
* **Client-side Scripting**: JavaScript (Vanilla) - Interactive functionality for web and mobile interfaces

### Optional Extensions

* **JavaScript Frameworks**: Vue.js or React (for complex UI components in applets)
* **PHP Libraries**: 
  - Composer for dependency management
  - Slim Framework or Laravel for API routing and structure
  - PDO for database abstraction
  - JWT for authentication tokens
  - PHPMailer for email notifications

### Infrastructure

* **Containerization**: Docker - For portable deployments across cloud/on-premise environments
* **Web Server**: Nginx or Apache - Serving static assets and proxying to PHP-FPM
* **Version Control**: Git - For collaborative development and deployment
* **Build Tools**: npm/yarn for frontend asset compilation, Composer for PHP dependencies

### Mobile Considerations

* **Hybrid Mobile App**: Capacitor or Cordova with web technologies for Android staff app
* **Progressive Web App (PWA)**: For enhanced mobile experience on public portal

This tech stack ensures:
- **Lightweight deployment** with SQLite's zero-configuration nature
- **Rapid development** using familiar web technologies
- **Scalability** through modular PHP architecture and containerization
- **Extensibility** via the applets system and optional JS frameworks

---

## 🔮 10. Conclusion and Future Vision

**AnimaID** is not just a management system, but a *modular ecosystem* designed to grow with the needs of animation centers.
The division between **core modules** and **applets** allows:

* high customization for each structure,
* agile feature management,
* technological evolution without invasive migrations.

**Possible Evolutions:**

* Internal **App Store system**
* Integration with educational platforms and digital payments
* Support for **public APIs** for external partners
* Automations via **event triggers**
* Predictive analytics to optimize planning

---

# 🏁 End of Document

*Document drafted for the architectural and functional design of the AnimaID system.*
Version: **0.9 — Draft Functional Architecture (October 2025)**
