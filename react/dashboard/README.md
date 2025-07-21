# SAVY Dashboard

A comprehensive analytics dashboard for the SAVY Conversational AI system, providing deep insights into query patterns, success rates, performance metrics, and team collaboration features.

## 🚀 Features

### 📊 **Analytics & Insights**
- Real-time query success/failure rate tracking
- Cost analysis and efficiency metrics
- Topic distribution analysis with 12 categories
- Time-based usage patterns and trends
- Performance optimization metrics

### 🎯 **Advanced Filtering**
- Query type filtering (successful, failed, all)
- Topic-based filtering (academic, campus, technical, etc.)
- Time range filtering (24h, 7d, 30d, all time)
- Cost efficiency filtering
- Assignment status filtering

### 👥 **Team Collaboration**
- Failed query assignment system
- Team member management
- Priority-based task assignment
- Progress tracking and status updates
- Assignment analytics

### 🔧 **Performance Features**
- Chunked loading for large datasets (17,000+ records)
- Optimized CSV processing
- Responsive UI with loading states
- Export functionality for filtered data

## 🛠️ Tech Stack

- **Frontend**: React 18, JavaScript ES6+
- **Styling**: Tailwind CSS
- **Charts**: Recharts for data visualization
- **Icons**: Lucide React
- **Data Processing**: Client-side CSV parsing
- **State Management**: React Hooks (useState, useEffect, useMemo)

## 📦 Installation

1. **Clone the repository**:
```bash
git clone <your-repository-url>
cd savy-dashboard
```

2. **Install dependencies**:
```bash
npm install
```

3. **Start the development server**:
```bash
npm start
```

4. **Open the dashboard**: Visit [http://localhost:3000](http://localhost:3000)

## 📊 Data Format

The dashboard expects CSV data with the following columns:

| Column | Description | Example |
|--------|-------------|---------|
| `prompt` | User query text | "How do I register for courses?" |
| `response` | AI response text | "You can register through the portal..." |
| `prompt_tokens` | Input tokens count | 12 |
| `completion_tokens` | Output tokens count | 45 |
| `cost` | Processing cost | 0.0023 |
| `timestamp` | Query timestamp | 2025-07-08T10:30:00Z |

## 🔧 Configuration

### **Data Loading Options**
1. **Real Data**: Place your data file as `public/savy_real_data.csv`
2. **File Upload**: Use the built-in file upload feature
3. **Sample Data**: Built-in sample data for testing

### **Performance Settings**
- Initial load: 1,000 records for fast startup
- Full dataset: Load complete data on demand
- Chunked processing: Prevents browser hanging

## 🎯 Usage Guide

### **Quick Start**
1. Dashboard loads with sample data for immediate analysis
2. Use "Load Full Dataset" button to analyze complete data
3. Apply filters to drill down into specific patterns
4. Export filtered results for further analysis

### **Team Assignment Workflow**
1. Navigate to "Failed Query Analysis" section
2. Review failed queries and their details
3. Assign queries to team members with priority levels
4. Track progress and resolution status
5. Export assignment reports

### **Analytics Features**
- **Success Rate**: Overall query success percentage
- **Cost Analysis**: Total costs and efficiency metrics
- **Usage Patterns**: Peak hours and activity trends
- **Topic Distribution**: Query categorization insights
- **Team Performance**: Assignment completion rates

## 📈 Topic Categories

The dashboard categorizes queries into 12 topics:

1. **Academic**: Courses, registration, grades, assignments
2. **Campus**: Library, dining, facilities, locations
3. **Student Life**: Mental health, clubs, support services
4. **Financial**: Scholarships, tuition, financial aid
5. **Technical**: IT support, login issues, software
6. **Admissions**: Applications, requirements, deadlines
7. **Events**: Programs, workshops, activities
8. **Policies**: Rules, regulations, procedures
9. **Navigation**: Directions, parking, transportation
10. **Contact**: Staff information, department contacts
11. **Services**: Support services, resources
12. **General**: Queries not fitting other categories

## 🚀 Deployment

### **Development**
```bash
npm start
```

### **Production Build**
```bash
npm run build
```

### **Deployment Options**
- **GitHub Pages**: Automatic deployment from repository
- **Netlify**: Drag and drop build folder
- **Vercel**: Connect GitHub repository
- **Custom Server**: Deploy build folder to web server

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🔗 Related Projects

- SAVY Conversational AI System
- University Student Support Platform
- AI Analytics Dashboard Framework

## 📞 Support

For questions or support, please contact the development team or open an issue in this repository.

## 🔄 Version History

- **v1.0.0**: Initial release with core analytics and team assignment features
- **v1.1.0**: Added performance optimization and chunked loading
- **v1.2.0**: Enhanced topic categorization and filtering
- **v1.3.0**: Team assignment system and collaboration features
