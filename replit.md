# Arbitragem Cripto - System Overview

## Overview

This is a PHP-based web application for managing cryptocurrency arbitrage operations and funding rate monitoring. The system provides complete authentication functionality and comprehensive operation tracking, storing all data in a MySQL database on Hostinger hosting. The application focuses on tracking arbitrage trades, profits, and financial operations in USD currency.

## User Preferences

Preferred communication style: Simple, everyday language in Portuguese.

## System Architecture

### Backend Architecture
- **Language**: PHP with PDO for database operations
- **Database**: MySQL hosted on Hostinger (srv1887.hstgr.io)
- **Authentication**: Session-based authentication with password recovery
- **Data Storage**: All financial data stored in USD currency
- **Connection Pattern**: PDO with UTF-8 charset and exception handling

### Frontend Architecture
- **Styling**: Custom CSS with CSS variables for theming
- **JavaScript**: Vanilla JavaScript for form validation and UX enhancements
- **Design**: Responsive design with Bootstrap-inspired components
- **User Experience**: Authentication pages with gradient backgrounds and glass morphism effects

### Database Schema
The system uses pre-existing MySQL tables with the following structure:
- `usuarios`: User authentication and profile data
- `operacoes`: Main arbitrage operations tracking (columns: `status_operacao`, `moeda_par`, `valor_inicial_usdt`, `valor_inicial_brl`, `data_fim`)
- `registros_lucro`: Intermediate profit recordings
- `sessoes`: Session management
- `password_resets`: Password recovery tokens

**Important**: Database uses `status_operacao` (not `status`), `moeda_par` (not `moeda`), `valor_inicial_usdt` (not `inicial_usdt`), and `data_fim` (not `data_fechamento`)

## Key Components

### Authentication System
- **User Registration**: New user account creation
- **Login System**: Email/password authentication
- **Password Recovery**: Email-based password reset functionality
- **Session Management**: Secure session handling

### Operation Management
- **Operation Entry**: Recording new investment positions
- **Profit Tracking**: Monitoring gains during active operations
- **Operation Exit**: Finalizing operations with balance calculations
- **Currency Handling**: All values stored and displayed in USD

### Database Connection
- **Host**: srv1887.hstgr.io (IP: 193.203.175.199)
- **Database**: u999216088_registrocripto
- **Username**: u999216088_registrocripto
- **Authentication**: Connected with provided credentials
- **Connection Method**: PDO with exception mode enabled
- **Status**: Successfully connected and operational

## Data Flow

1. **User Authentication**: Users authenticate through the login system
2. **Operation Creation**: New arbitrage operations are recorded with initial BRL and USDT values
3. **Profit Recording**: Intermediate profits are logged during operation lifecycle
4. **Operation Closure**: Final results and balances are calculated and stored
5. **Data Persistence**: All operation data is stored in MySQL for historical tracking

## External Dependencies

### Hosting Infrastructure
- **Provider**: Hostinger hosting service
- **Database**: MySQL server with UTF-8 support
- **Email Service**: Required for password recovery functionality

### Frontend Libraries
- **CSS Framework**: Custom CSS with modern design patterns
- **JavaScript**: Vanilla JS for enhanced user interactions
- **Responsive Design**: Mobile-first approach with flexible layouts

## Deployment Strategy

### Database Configuration
- Pre-configured MySQL database with established tables
- Connection credentials hardcoded for Hostinger environment
- UTF-8 charset configuration for international support

### Application Structure
- **Authentication Layer**: Complete user management system
- **Business Logic**: Arbitrage operation tracking and calculation
- **Data Layer**: PDO-based database operations with error handling
- **Presentation Layer**: Responsive web interface with enhanced UX

### Security Considerations
- PDO prepared statements for SQL injection prevention
- Session-based authentication
- Password hashing (implementation dependent on existing code)
- Email-based password recovery system

## Recent Changes

### January 22, 2025
- **Database Connection**: Successfully connected to Hostinger MySQL database
- **Schema Alignment**: Fixed all database column mismatches across application files
- **Navigation Fix**: Resolved CSS/JS path issues for operations subdirectory pages
- **File Updates**: Updated all operation files to use correct database schema
- **Design Overhaul**: Complete visual redesign with advanced tech aesthetic:
  - Dark background with glassmorphism effects
  - Neon blue/cyan accent colors and gradients
  - Animated particle backgrounds for auth pages
  - Tech-inspired cards and dashboard components
  - Sophisticated navigation with gradient text
  - Professional empty states and status badges
- **User Interface**: Removed bright/colorful elements, implemented sophisticated dark theme
- **Typography**: Added Inter font family for modern tech appearance
- **Mobile Optimization**: Enhanced mobile layout with 2x2 grid for metric cards
- **Form Enhancement**: Expanded operation form to support dual arbitrage model:
  - Added complete arbitrage tracking fields
  - Separate sections for MEXC (Long) and BTCC (Short) positions
  - Comprehensive data capture for both exchanges
  - Visual distinction between long/short positions
  - Support for detailed operation parameters
- **Button Layout**: Optimized button positioning for mobile interface
- **Status**: System fully operational with premium tech visual design and complete arbitrage support
- **Interface Optimization**: Removed all card subtitles for ultra-clean dashboard interface:
  - Eliminated "Em tempo real", "Total alocado", "Em progresso", "Lucro / Capital", "Soma total", "ROI / Dias"
  - Reduced card height from 100px to 50px minimum for compact design
  - Decreased padding and font sizes for optimal space utilization
  - Maintained professional tech aesthetic with enhanced minimalism
- **Mobile Operations Table**: Optimized for mobile devices:
  - Dual layout: Desktop table view + Mobile card view
  - Mobile cards eliminate horizontal scrolling
  - Touch-friendly buttons and spacing
  - Responsive filter system with collapsible controls
- **Enhanced Operation Details Page**: Complete redesign for better data visualization:
  - Detailed profit history table with date, time, and value columns
  - Compact table layout optimized for space efficiency
  - Tech-themed styling with proper contrast and readability
  - Balance addition history with MEXC/BTCC position details
  - Single responsive table format replacing card layouts for better space utilization
  - Streamlined profit table: Removed "Tipo" and "Observações" columns for cleaner layout
- **UI Enhancement**: Applied rounded borders (12px) to all form inputs for consistent modern design
- **New Metric Cards**: Added two new cards to dashboard:
  - "Quantidade de dias Operados": Counts unique profit recording days
  - "Média de % por dia": Average ROI percentage per operating day
  - Optimized grid layout: 2 cards per row on mobile, 6 columns on large screens
  - Mobile optimization: CSS Grid layout, reduced height (100px), perfect lateral spacing
- **Balance Addition System**: Implemented system for adding balance to existing operations:
  - Created `adicoes_saldo` table to track balance additions with full transparency
  - Added balance addition form with complete arbitrage data capture
  - Updated operation list and view pages with balance addition buttons
  - Historical tracking of all balance additions with MEXC/BTCC position details
  - Automatic update of main operation total when balance is added
  - Maintains transparency of original values and all subsequent additions
- **Profit Evolution Chart**: Advanced data visualization system implemented:
  - Chart.js integration with real-time database connectivity
  - Dynamic period filters (7, 15, 30, 60, 90 days)
  - Triple-mode display: Daily profits (ondulado), Cumulative USDT, and ROI percentage
  - Connected to `registros_lucro` table for authentic profit tracking
  - Daily variation chart shows profit fluctuations day by day
  - Interactive mode switching with visual feedback
  - Dynamic colors: green for daily profits, cyan for cumulative/ROI
  - AJAX-powered data fetching with seamless user experience

The system is designed for personal use with a focus on tracking cryptocurrency arbitrage operations, providing a comprehensive solution for monitoring funding rate arbitrage trades and profitability analysis.