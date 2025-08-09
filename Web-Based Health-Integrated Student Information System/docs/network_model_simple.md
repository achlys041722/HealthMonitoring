# Figure 3. Network Model: Internet-based Client-Server
## Web-Based Health-Integrated Student Information System

```
┌─────────────────────────────────────────────────────────────────────────────────────────────────┐
│                              HEALTH-INTEGRATED STUDENT INFO SYSTEM MAIN SERVER                  │
│                                                                                                 │
│  (Export student and health records)                                                            │
└─────────────────────────────────────────────────────────────────────────────────────────────────┘
                                            │
                                            ▼
┌─────────────────────────────────────────────────────────────────────────────────────────────────┐
│                              WEB-BASED APPLICATION                                              │
│                                                                                                 │
│  ♦ Client Layer                                                                                │
│    • HTML5, CSS3, JavaScript (Bootstrap)                                                       │
│    • Responsive Web Interface                                                                  │
│                                                                                                 │
│  ♦ Application Layer                                                                           │
│    • PHP Business Logic                                                                        │
│    • Session Management                                                                        │
│    • Role-Based Access Control                                                                 │
│                                                                                                 │
│  ♦ Data Layer                                                                                  │
│    • MySQL Database Management                                                                 │
│    • Student Records Storage                                                                   │
│    • Health Information Database                                                               │
└─────────────────────────────────────────────────────────────────────────────────────────────────┘
                                            │
                                            ▼
                    ┌─────────────────────────────────────────────────────────────────┐
                    │                    INTERNET ACCESS                              │
                    └─────────────────────────────────────────────────────────────────┘
                                            │
                    ┌─────────────────────────────────────────────────────────────────┐
                    │                                                               │
                    │  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
                    │  │   PRINCIPAL │    │   TEACHER   │    │    NURSE    │        │
                    │  │   COMPUTER  │    │   COMPUTER  │    │   COMPUTER  │        │
                    │  │             │    │             │    │             │        │
                    │  │  Desktop    │    │  Desktop    │    │  Desktop    │        │
                    │  │  Monitor    │    │  Monitor    │    │  Monitor    │        │
                    │  └─────────────┘    └─────────────┘    └─────────────┘        │
                    │                                                               │
                    │  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
                    │  │   PRINCIPAL │    │   TEACHER   │    │    NURSE    │        │
                    │  │   DEVICES   │    │   DEVICES   │    │   DEVICES   │        │
                    │  │             │    │             │    │             │        │
                    │  │  Laptop     │    │  Laptop     │    │  Laptop     │        │
                    │  │  Tablet     │    │  Tablet     │    │  Tablet     │        │
                    │  │  Mobile     │    │  Mobile     │    │  Mobile     │        │
                    │  └─────────────┘    └─────────────┘    └─────────────┘        │
                    │                                                               │
                    └─────────────────────────────────────────────────────────────────┘
```

## Network Architecture Overview

The system follows a **3-Tier Architecture**:

1. **Client Layer**: User interface and interactions
2. **Application Layer**: Business logic and processing
3. **Data Layer**: Database management and storage

Users access the system through various devices (Desktop, Laptop, Tablet, Mobile) via web browsers over the internet. 