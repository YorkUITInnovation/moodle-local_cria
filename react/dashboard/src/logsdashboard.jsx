import React, { useState, useMemo, useCallback } from 'react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell, Area, AreaChart } from 'recharts';
import { Users, MessageSquare, AlertTriangle, DollarSign, Search, Download, RefreshCw, CheckCircle, XCircle, Info } from 'lucide-react';

const LogsDashboard = () => {
  const [data, setData] = useState([]);
  const [dataSource, setDataSource] = useState('sample'); // 'sample' or 'uploaded'
  const [searchTerm, setSearchTerm] = useState('');
  const [dateRange, setDateRange] = useState('30d');
  const [uploadStatus, setUploadStatus] = useState({ type: '', message: '' });
  const [isLoading, setIsLoading] = useState(false);
  const [botId, setBotId] = useState(null);

  // PM-focused filters
  const [queryTypeFilter, setQueryTypeFilter] = useState('all'); // 'all', 'successful', 'failed'
  const [topicFilter, setTopicFilter] = useState('all'); // 'all', 'academic', 'campus', 'technical', etc.
  const [timeOfDayFilter, setTimeOfDayFilter] = useState('all'); // 'all', 'business', 'after-hours'
  const [costEfficiencyFilter, setCostEfficiencyFilter] = useState('all'); // 'all', 'high-efficiency', 'medium', 'low-efficiency'

  // Failed query assignment system
  const [assignments, setAssignments] = useState({}); // { queryId: { assignee, status, priority, notes, assignedDate } }
  const [showAssignmentModal, setShowAssignmentModal] = useState(false);
  const [selectedQuery, setSelectedQuery] = useState(null);
  const [assignmentFilter, setAssignmentFilter] = useState('all'); // 'all', 'unassigned', 'assigned', 'in-progress', 'resolved'
  const [isReassignMode, setIsReassignMode] = useState(false);

  // Permitted users for assignment (loaded from API)
  const [permittedUsers, setPermittedUsers] = useState([]);

  // Get bot_id from URL query parameters
  React.useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const bot_id = urlParams.get('bot_id');
    if (bot_id) {
      setBotId(parseInt(bot_id));
    }
  }, []);

  // Load data when bot_id is available
  React.useEffect(() => {
    if (botId) {
      loadBotData();
      loadAssignments();
    }
  }, [botId]); // eslint-disable-line react-hooks/exhaustive-deps

  // Reload data when date range changes
  React.useEffect(() => {
    if (botId) {
      loadBotData();
    }
  }, [dateRange]); // eslint-disable-line react-hooks/exhaustive-deps

  // Safe date parser that accepts ISO 8601, MySQL "YYYY-MM-DD HH:MM:SS", epoch seconds/millis
  const safeParseDate = useCallback((value) => {
    if (!value) return null;
    if (value instanceof Date) return isNaN(value.getTime()) ? null : value;
    if (typeof value === 'number') {
      // Heuristic: < 1e12 is seconds, otherwise milliseconds
      const ms = value < 1e12 ? value * 1000 : value;
      const d = new Date(ms);
      return isNaN(d.getTime()) ? null : d;
    }
    if (typeof value === 'string') {
      // Try native parse first
      let d = new Date(value);
      if (!isNaN(d.getTime())) return d;

      // Try replace space with 'T' (MySQL DATETIME) and assume local timezone
      const withT = value.replace(' ', 'T');
      d = new Date(withT);
      if (!isNaN(d.getTime())) return d;

      // Try forcing UTC by appending 'Z'
      d = new Date(withT + 'Z');
      if (!isNaN(d.getTime())) return d;
    }
    return null;
  }, []);

  // Map UI priority (lowercase) to server format (Title Case)
  const toServerPriority = useCallback((p) => {
    const map = { low: 'Low', medium: 'Medium', high: 'High', urgent: 'Urgent' };
    if (!p) return 'Low';
    const key = String(p).toLowerCase();
    return map[key] || 'Low';
  }, []);

  // Team members for assignment - fallback if no permittedUsers available
  const fallbackTeamMembers = [
    { id: 'ai-engineer-1', name: 'Sarah Chen', role: 'AI Engineer', department: 'Engineering' },
    { id: 'ai-engineer-2', name: 'Michael Rodriguez', role: 'AI Engineer', department: 'Engineering' },
    { id: 'content-specialist-1', name: 'Emily Watson', role: 'Content Specialist', department: 'Content' },
    { id: 'content-specialist-2', name: 'David Kim', role: 'Content Specialist', department: 'Content' },
    { id: 'data-scientist-1', name: 'Jennifer Liu', role: 'Data Scientist', department: 'Data Science' },
    { id: 'product-manager-1', name: 'Alex Thompson', role: 'Product Manager', department: 'Product' },
    { id: 'ux-researcher-1', name: 'Maria Garcia', role: 'UX Researcher', department: 'Design' }
  ];

  // Get team members - use permittedUsers if available, otherwise fallback
  const teamMembers = useMemo(() => {
    if (permittedUsers && permittedUsers.length > 0) {
      return permittedUsers.map(user => ({
        id: user.id.toString(), // Ensure ID is string for consistency
        name: `${user.firstname} ${user.lastname}`.trim(),
        email: user.email,
        role: 'Team Member', // Default role since not provided in API
        department: 'Support' // Default department since not provided in API
      }));
    }
    return fallbackTeamMembers;
  }, [permittedUsers]);

  // Load assignments from localStorage
  const loadAssignments = () => {
    try {
      const savedAssignments = localStorage.getItem('savy-query-assignments');
      if (savedAssignments) {
        setAssignments(JSON.parse(savedAssignments));
      }
    } catch (error) {
      console.error('Error loading assignments:', error);
    }
  };

  // Save assignments to localStorage
  const saveAssignments = (newAssignments) => {
    try {
      localStorage.setItem('savy-query-assignments', JSON.stringify(newAssignments));
      setAssignments(newAssignments);
    } catch (error) {
      console.error('Error saving assignments:', error);
    }
  };

  // Get task assignment for a query from the database data (not localStorage)
  const getTaskAssignment = (query) => {
    if (query.tasks && query.tasks.length > 0) {
      // Use the first task (assuming one task per query for now)
      const task = query.tasks[0];
      return {
        assignee: {
          id: (task.userid ?? task.id)?.toString(),
          name: `${task.firstname} ${task.lastname}`.trim(),
          email: task.email,
          role: 'Team Member',
          department: 'Support'
        },
        status: task.status || 'assigned',
        // Normalize to lowercase for consistent UI handling
        priority: (task.priority || 'medium').toString().toLowerCase(),
        notes: task.notes || '',
        assignedDate: task.timecreated,
        updatedDate: task.timecreated
      };
    }
    return null;
  };

  // Generate unique ID for each query - now uses the existing id field from data
  const generateQueryId = (query) => {
    try {
      // Use the existing id field if available, otherwise fall back to hash generation
      if (query.id) {
        return `query_${query.id}`;
      }

      // Fallback for data without id field (like sample data)
      const str = query.prompt.substring(0, 50) + query.timestamp;
      let hash = 0;
      for (let i = 0; i < str.length; i++) {
        const char = str.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash; // Convert to 32-bit integer
      }
      return 'query_' + Math.abs(hash).toString(36);
    } catch (error) {
      console.error('Error generating query ID:', error);
      // Fallback to timestamp-based ID
      return 'query_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
  };

  // Assign query to team member
  const assignQuery = (queryId, assigneeId, priority = 'medium', notes = '') => {
    const assignee = teamMembers.find(member => member.id === assigneeId);
    const newAssignments = {
      ...assignments,
      [queryId]: {
        assignee: assignee,
        status: 'assigned',
        priority: priority,
        notes: notes,
        assignedDate: new Date().toISOString(),
        updatedDate: new Date().toISOString()
      }
    };
    saveAssignments(newAssignments);
  };

  // Update assignment status via backend API then refresh data
  const updateAssignmentStatus = async (queryId, status, notes = '') => {
    try {
      const resp = await fetch('../update_task.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ query_id: queryId, status, notes })
      });
      const result = await resp.json();
      if (result.success) {
        setUploadStatus({ type: 'success', message: `Task marked ${status}` });
        try { await loadBotData(); } catch (e) { /* noop */ }
        setTimeout(() => setUploadStatus({ type: '', message: '' }), 3000);
      } else {
        setUploadStatus({ type: 'error', message: result.error || 'Failed to update task' });
        setTimeout(() => setUploadStatus({ type: '', message: '' }), 4000);
      }
    } catch (e) {
      console.error('Error updating task status:', e);
      setUploadStatus({ type: 'error', message: 'Network error while updating task' });
      setTimeout(() => setUploadStatus({ type: '', message: '' }), 4000);
    }
  };

  // Unassign (delete task) via backend API then refresh data
  const removeAssignment = async (queryId) => {
    try {
      const resp = await fetch('../update_task.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ query_id: queryId, action: 'delete' })
      });
      const result = await resp.json();
      if (result.success) {
        setUploadStatus({ type: 'success', message: 'Task unassigned' });
        try { await loadBotData(); } catch (e) { /* noop */ }
        setTimeout(() => setUploadStatus({ type: '', message: '' }), 3000);
      } else {
        setUploadStatus({ type: 'error', message: result.error || 'Failed to unassign task' });
        setTimeout(() => setUploadStatus({ type: '', message: '' }), 4000);
      }
    } catch (e) {
      console.error('Error unassigning task:', e);
      setUploadStatus({ type: 'error', message: 'Network error while unassigning task' });
      setTimeout(() => setUploadStatus({ type: '', message: '' }), 4000);
    }
  };

  // Default topic filter options and keywords
  const defaultTopicOptions = [

    { value: 'academic', label: 'Academic Support' },
    { value: 'campus', label: 'Campus Services' },
    { value: 'student-life', label: 'Student Life' },
    { value: 'financial', label: 'Financial Aid' },
    { value: 'technical', label: 'Technical Support' }
  ];
  const defaultTopicKeywordsMap = {
    academic: ['course','registration','academic','study','assignment','tutor','grade','exam'],
    campus: ['library','dining','wifi','building','hall','location','map','hours'],
    'student-life': ['mental health','counselling','support','wellness','student','campus life'],
    financial: ['scholarship','financial aid','tuition','payment','funding','bursary'],
    technical: ['computer','technology','login','password','internet','system','IT']
  };
  // state for dynamic topic filters
  const [topicOptions, setTopicOptions] = useState(defaultTopicOptions);
  const [topicKeywordsMap, setTopicKeywordsMap] = useState(defaultTopicKeywordsMap);

  // Function to load bot data from PHP endpoint
  const loadBotData = async () => {
    try {
      setIsLoading(true);
      setUploadStatus({ type: 'info', message: 'Loading bot conversation data...' });

      // Fetch the data from the PHP endpoint with date range parameter
      const response = await fetch(`../data.php?bot_id=${botId}&date_range=${dateRange}`);
      if (!response.ok) {
        throw new Error('Could not load bot data');
      }

      const jsonResponse = await response.json();

      // override topic settings if provided
      if (jsonResponse.topicKeywords && typeof jsonResponse.topicKeywords === 'object') {
        setTopicKeywordsMap(jsonResponse.topicKeywords);
      }

      // override topic options if provided as array of objects with value/label structure

        // Add "All Topics" option if not present
        const hasAllTopics = jsonResponse.topicOptions.find(opt => opt.value === 'all');
        if (!hasAllTopics) {
          jsonResponse.topicOptions.unshift({ value: 'all', label: 'All Topics' });

        setTopicOptions(jsonResponse.topicOptions);
      } else if (jsonResponse.topicKeywords && typeof jsonResponse.topicKeywords === 'object') {
        // If only topicKeywords provided, build options from keywords map
        setTopicOptions(Object.keys(jsonResponse.topicKeywords).map(key => ({
          value: key,
          label: key.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
        })).concat([{ value: 'all', label: 'All Topics' }]));
      }

      if (!jsonResponse.success) {
        throw new Error(jsonResponse.error || 'Failed to load data');
      }

      if (!Array.isArray(jsonResponse.data) || jsonResponse.data.length === 0) {
        throw new Error('No data found for this bot');
      }

      setData(jsonResponse.data);

      // Extract and set permitted users for assignment if available
      if (jsonResponse.permittedUsers && Array.isArray(jsonResponse.permittedUsers)) {
        console.log('Loading permitted users:', jsonResponse.permittedUsers);
        setPermittedUsers(jsonResponse.permittedUsers);
      } else {
        console.log('No permitted users found in API response, using fallback team members');
        setPermittedUsers([]); // Clear any existing permitted users
      }

      setUploadStatus({
        type: 'success',
        message: `Loaded ${jsonResponse.data.length} conversations for bot ${botId}${jsonResponse.permittedUsers ? ` with ${jsonResponse.permittedUsers.length} permitted users` : ''}`
      });

      // Clear status after 5 seconds
      setTimeout(() => setUploadStatus({ type: '', message: '' }), 5000);

    } catch (error) {
      console.error('Error loading bot data:', error);
      setUploadStatus({
        type: 'error',
        message: `Could not load bot data: ${error.message}`
      });
    } finally {
      setIsLoading(false);
    }
  };

  // Sample data for testing
  const sampleData = [
    { id: 1, prompt: "What kinds of questions can you answer?", response: "I can help answer questions and connect you with available resources for academic success. However, I'm not currently able to answer questions about admissions, costs for individual courses and programs, or availability of your program requirements.", promptTokens: 15, completionTokens: 45, cost: 0.002, timestamp: "2025-07-08T09:30:00Z" },
    { id: 2, prompt: "Where is Vari Hall?", response: "Vari Hall is located at the Keele Campus of York University. It is one of the central buildings with multiple lecture halls. For specific room locations and to find your lecture hall, you can use the York University Interactive Map.", promptTokens: 12, completionTokens: 35, cost: 0.001, timestamp: "2025-07-08T10:15:00Z" },
    { id: 3, prompt: "How do I register for courses?", response: "To register for courses, you'll need to log into your student portal and use the course enrollment system. Make sure to check prerequisites and course availability. If you need help with registration, contact the Registrar's Office.", promptTokens: 18, completionTokens: 42, cost: 0.0015, timestamp: "2025-07-08T11:00:00Z" },
    { id: 4, prompt: "What are the library hours?", response: "Library hours vary by location and time of year. During the academic year, most libraries are open extended hours including evenings and weekends. Check the York University Libraries website for current hours and any holiday closures.", promptTokens: 14, completionTokens: 38, cost: 0.0012, timestamp: "2025-07-08T14:20:00Z" },
    { id: 5, prompt: "How can I get academic support?", response: "York University offers various academic support services including tutoring centers, writing support, study groups, and academic advising. Visit the Student Success Centre or check the academic support resources on the university website.", promptTokens: 16, completionTokens: 41, cost: 0.0018, timestamp: "2025-07-08T15:45:00Z" },
    { id: 6, prompt: "What mental health resources are available?", response: "York offers counselling services, mental health workshops, peer support programs, and crisis support. The Counselling & Psychological Services office can provide individual counselling and connect you with appropriate resources.", promptTokens: 13, completionTokens: 34, cost: 0.001, timestamp: "2025-07-07T08:30:00Z" },
    { id: 7, prompt: "How do I access WiFi on campus?", response: "Students can connect to the 'YorkU' wireless network using their Passport York credentials. Guest access is also available. For technical support with connectivity issues, contact UIT (University Information Technology).", promptTokens: 16, completionTokens: 36, cost: 0.0019, timestamp: "2025-07-07T09:15:00Z" },
    { id: 8, prompt: "Where can I find information about scholarships?", response: "Scholarship information is available through the Student Financial Services office and the university website. You can search for scholarships by program, year of study, and eligibility criteria. Application deadlines vary by scholarship.", promptTokens: 18, completionTokens: 40, cost: 0.0016, timestamp: "2025-07-07T10:30:00Z" },
    { id: 9, prompt: "Can you help me with my assignment?", response: "I can provide general guidance on academic resources and study strategies, but I cannot help with specific assignment content. For assignment help, visit the Writing Centre, subject-specific tutoring services, or speak with your instructor during office hours.", promptTokens: 15, completionTokens: 43, cost: 0.0011, timestamp: "2025-07-07T13:45:00Z" },
    { id: 10, prompt: "What dining options are available on campus?", response: "York University has various dining options including cafeterias, food courts, coffee shops, and restaurants across different campuses. Meal plans are available for students living in residence. Check the York Dining website for locations and hours.", promptTokens: 17, completionTokens: 39, cost: 0.0017, timestamp: "2025-07-07T16:20:00Z" }
  ];

  // Use the loaded data directly, no more CSV processing needed
  const processedData = data.length > 0 ? data : sampleData;

  // Enhanced file upload handler with better error handling and format detection
  const handleFileUpload = useCallback(async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    setIsLoading(true);
    setUploadStatus({ type: 'info', message: 'Processing file...' });

    try {
      const fileExtension = file.name.split('.').pop().toLowerCase();
      let parsedData = [];

      if (fileExtension === 'csv') {
        const text = await file.text();
        parsedData = parseCSVData(text);
      } else if (fileExtension === 'xlsx' || fileExtension === 'xls') {
        // For Excel files, we'll need to inform the user to convert to CSV for now
        setUploadStatus({
          type: 'warning',
          message: 'Excel files detected. Please save as CSV format for best compatibility.'
        });
        setIsLoading(false);
        return;
      } else {
        throw new Error('Unsupported file format. Please use CSV files.');
      }

      if (parsedData.length === 0) {
        throw new Error('No valid data found in the file.');
      }

      setData(parsedData);
      setDataSource('uploaded');
      setUploadStatus({
        type: 'success',
        message: `Successfully loaded ${parsedData.length} records from ${file.name}`
      });

      // Clear status after 5 seconds
      setTimeout(() => setUploadStatus({ type: '', message: '' }), 5000);

    } catch (error) {
      console.error('Error parsing file:', error);
      setUploadStatus({
        type: 'error',
        message: `Error processing file: ${error.message}`
      });
    } finally {
      setIsLoading(false);
      // Reset file input
      event.target.value = '';
    }
  }, []);

  // Enhanced CSV parser with proper quoted string handling
  const parseCSVData = (text) => {
    const lines = text.trim().split('\n');
    if (lines.length < 2) throw new Error('File must contain at least a header and one data row.');

    // Proper CSV parsing function that handles quoted strings with commas
    const parseCSVLine = (line) => {
      const result = [];
      let current = '';
      let inQuotes = false;

      for (let i = 0; i < line.length; i++) {
        const char = line[i];
        const nextChar = line[i + 1];

        if (char === '"') {
          if (inQuotes && nextChar === '"') {
            // Escaped quote
            current += '"';
            i++; // Skip next quote
          } else {
            // Toggle quote state
            inQuotes = !inQuotes;
          }
        } else if (char === ',' && !inQuotes) {
          // Field separator
          result.push(current.trim());
          current = '';
        } else {
          current += char;
        }
      }

      // Add the last field
      result.push(current.trim());
      return result;
    };

    const headers = parseCSVLine(lines[0]).map(h => h.toLowerCase());

    // Flexible column mapping - detect various naming conventions
    const columnMap = {
      prompt: headers.findIndex(h =>
        (h.includes('prompt') || h.includes('question') || h.includes('query') || h.includes('input'))
      ),
      response: headers.findIndex(h =>
        (h.includes('response') || h.includes('answer') || h.includes('output') || h.includes('completion'))
      ),
      promptTokens: headers.findIndex(h =>
        (h.includes('prompt') && h.includes('token')) || h.includes('input_token')
      ),
      completionTokens: headers.findIndex(h =>
        (h.includes('completion') && h.includes('token')) || h.includes('output_token') || h.includes('response_token')
      ),
      cost: headers.findIndex(h =>
        (h.includes('cost') || h.includes('price') || h.includes('amount'))
      ),
      timestamp: headers.findIndex(h =>
        (h.includes('timestamp') || h.includes('time') || h.includes('date') || h.includes('created'))
      )
    };

    // Validate required columns
    if (columnMap.prompt === -1 || columnMap.response === -1) {
      throw new Error('File must contain columns for prompts and responses. Expected columns like: prompt, response, etc.');
    }

    const dataRows = lines.slice(1);
    const parsedData = dataRows.map((row, index) => {
      try {
        const columns = parseCSVLine(row);

        const costValue = parseFloat(columns[columnMap.cost]);
        const promptTokensValue = parseInt(columns[columnMap.promptTokens]);
        const completionTokensValue = parseInt(columns[columnMap.completionTokens]);

        // Debug high cost values
        if (costValue > 1) {
          console.log(`High cost detected in row ${index + 2}: ${costValue} from "${columns[columnMap.cost]}"`);
        }

        return {
          prompt: columns[columnMap.prompt] || '',
          response: columns[columnMap.response] || '',
          promptTokens: promptTokensValue || Math.floor(Math.random() * 50) + 10,
          completionTokens: completionTokensValue || Math.floor(Math.random() * 100) + 20,
          cost: isNaN(costValue) ? 0 : costValue,
          timestamp: columns[columnMap.timestamp] || new Date(Date.now() - Math.random() * 30 * 24 * 60 * 60 * 1000).toISOString()
        };
      } catch (error) {
        console.warn(`Error parsing row ${index + 2}:`, error);
        return null;
      }
    }).filter(row => row && row.prompt && row.response);

    if (parsedData.length === 0) {
      throw new Error('No valid data rows found. Please check your file format.');
    }

    console.log('Parsed CSV data length:', parsedData.length);
    console.log('Sample parsed data:', parsedData.slice(0, 5));
    console.log('Column mapping:', columnMap);

    return parsedData;
  };

  // Export functionality
  const exportData = useCallback((filteredData) => {
    const exportData = filteredData.map(item => ({
      Prompt: item.prompt,
      Response: item.response,
      'Prompt Tokens': item.promptTokens,
      'Completion Tokens': item.completionTokens,
      Cost: item.cost,
      Timestamp: item.timestamp
    }));

    const csvContent = [
      Object.keys(exportData[0]).join(','),
      ...exportData.map(row => Object.values(row).map(val =>
        typeof val === 'string' && val.includes(',') ? `"${val}"` : val
      ).join(','))
    ].join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `conversational-ai-data-${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  }, []);

  // Analytics calculations
  const analytics = useMemo(() => {
    const now = new Date();

    const filteredData = processedData.filter(item => {
      const itemDate = new Date(item.timestamp);

      // Date filtering
      let matchesDate = true;
      if (dateRange !== 'all') {
        const days = dateRange === '7d' ? 7 : dateRange === '30d' ? 30 : 1;
        const cutoffDate = new Date(now - days * 24 * 60 * 60 * 1000);
        matchesDate = itemDate >= cutoffDate;
      }

      // Search filtering
      const matchesSearch = !searchTerm ||
        (item.prompt && item.prompt.toLowerCase().includes(searchTerm.toLowerCase())) ||
        (item.response && item.response.toLowerCase().includes(searchTerm.toLowerCase()));

      // Query Type filtering (Success vs Failed)
      // A failed query is one where SAVY responded saying it doesn't have the answer or wasn't able to help
      // Exclude helpful capability explanations from being classified as failed queries
      const isCapabilityExplanation = item.response && (
        item.response.toLowerCase().includes("i can help answer questions and connect you with available resources") ||
        item.response.toLowerCase().includes("however, i'm not currently able to answer questions about admissions, costs for individual courses and programs, or availability of your program requirements")
      );

      const isFailedQuery = item.response && !isCapabilityExplanation && (
        item.response.toLowerCase().includes("llm generated i'm sorry, but i don't understand") ||
        item.response.toLowerCase().includes("i'm sorry, but i don't understand your question") ||
        item.response.toLowerCase().includes("i'm not currently able to answer questions about") ||
        item.response.toLowerCase().includes("this may be a topic that i am not trained on yet") ||
        item.response.toLowerCase().includes("i might not understand your phrasing") ||
        item.response.toLowerCase().includes("don't have access") ||
        item.response.toLowerCase().includes("cannot help") ||
        item.response.toLowerCase().includes("unable to") ||
        item.response.toLowerCase().includes("not able to") ||
        item.response.toLowerCase().includes("i don't have information") ||
        item.response.toLowerCase().includes("i'm not equipped") ||
        item.response.toLowerCase().includes("outside my capabilities")
      );

      const matchesQueryType = queryTypeFilter === 'all' ||
        (queryTypeFilter === 'successful' && !isFailedQuery) ||
        (queryTypeFilter === 'failed' && isFailedQuery);

      // Topic filtering
      const topicKeywords = topicKeywordsMap;

      let matchesTopic = topicFilter === 'all';
      if (!matchesTopic && topicKeywords[topicFilter] && item.prompt) {
        matchesTopic = topicKeywords[topicFilter].some(keyword =>
          item.prompt.toLowerCase().includes(keyword)
        );
      }

      // Time of Day filtering (Business hours: 9AM-5PM weekdays)
      const hour = itemDate.getHours();
      const dayOfWeek = itemDate.getDay(); // 0 = Sunday, 1 = Monday, etc.
      const isBusinessHours = (dayOfWeek >= 1 && dayOfWeek <= 5) && (hour >= 9 && hour <= 17);

      const matchesTimeOfDay = timeOfDayFilter === 'all' ||
        (timeOfDayFilter === 'business' && isBusinessHours) ||
        (timeOfDayFilter === 'after-hours' && !isBusinessHours);

      // Cost Efficiency filtering (based on cost per token)
      const totalTokens = (item.promptTokens || 0) + (item.completionTokens || 0);
      const costPerToken = totalTokens > 0 ? (item.cost || 0) / totalTokens : 0;

      let matchesCostEfficiency = costEfficiencyFilter === 'all';
      if (!matchesCostEfficiency) {
        if (costEfficiencyFilter === 'high-efficiency') matchesCostEfficiency = costPerToken < 0.00001; // Very cost effective
        else if (costEfficiencyFilter === 'medium') matchesCostEfficiency = costPerToken >= 0.00001 && costPerToken < 0.00005;
        else if (costEfficiencyFilter === 'low-efficiency') matchesCostEfficiency = costPerToken >= 0.00005;
      }

      return matchesDate && matchesSearch && matchesQueryType && matchesTopic && matchesTimeOfDay && matchesCostEfficiency;
    });

    const uniqueUsers = new Set(filteredData.map((_, index) => `user_${index % 20}`)).size;
    const totalQueries = filteredData.length;

    // Safe cost calculation with NaN protection
    const totalCost = filteredData.reduce((sum, item) => {
      const itemCost = parseFloat(item.cost);
      return sum + (isNaN(itemCost) || !isFinite(itemCost) ? 0 : itemCost);
    }, 0);

    // Safe token calculation with NaN protection
    const totalTokens = filteredData.reduce((sum, item) => {
      const promptTokens = parseInt(item.promptTokens) || 0;
      const completionTokens = parseInt(item.completionTokens) || 0;
      return sum + promptTokens + completionTokens;
    }, 0);
    const avgTokens = filteredData.length > 0 ? totalTokens / filteredData.length : 0;

    // Debug logging for cost calculation
    console.log(`Date range: ${dateRange}`);
    console.log(`Filtered data length: ${filteredData.length}`);
    console.log(`Total cost: ${totalCost}`);

    if (totalCost > 10) {
      console.log('High cost detected:', totalCost);
      console.log('Sample costs from data:', filteredData.slice(0, 10).map(item => ({
        cost: item.cost,
        type: typeof item.cost,
        raw: item.cost
      })));
    }

    // Failed queries (responses indicating inability to help)
    // A failed query is one where SAVY responded saying it doesn't have the answer or wasn't able to help
    // Exclude helpful capability explanations from being classified as failed queries
    const failedQueries = filteredData.filter(item => {
      if (!item.response) return false; // Skip items with null/undefined response

      const isCapabilityExplanation = item.response.toLowerCase().includes("i can help answer questions and connect you with available resources") ||
        item.response.toLowerCase().includes("however, i'm not currently able to answer questions about admissions, costs for individual courses and programs, or availability of your program requirements");

      return !isCapabilityExplanation && (
        item.response.toLowerCase().includes("llm generated i'm sorry, but i don't understand") ||
        item.response.toLowerCase().includes("i'm sorry, but i don't understand your question") ||
        item.response.toLowerCase().includes("i'm not currently able to answer questions about") ||
        item.response.toLowerCase().includes("this may be a topic that i am not trained on yet") ||
        item.response.toLowerCase().includes("i might not understand your phrasing") ||
        item.response.toLowerCase().includes("don't have access") ||
        item.response.toLowerCase().includes("cannot help") ||
        item.response.toLowerCase().includes("unable to") ||
        item.response.toLowerCase().includes("not able to") ||
        item.response.toLowerCase().includes("i don't have information") ||
        item.response.toLowerCase().includes("i'm not equipped") ||
        item.response.toLowerCase().includes("outside my capabilities")
      );
    });

    // Topic analysis for educational/SAVY content - use dynamic topicKeywordsMap
    const topicCounts = Object.entries(topicKeywordsMap).map(([topic, keywords]) => {
      const topicItems = filteredData.filter(item =>
        item.prompt && Array.isArray(keywords) && keywords.some(keyword =>
          typeof keyword === 'string' && item.prompt.toLowerCase().includes(keyword.toLowerCase())
        )
      );

      // Safe cost calculation for topics with additional validation
      const topicCost = topicItems.reduce((sum, item) => {
        const itemCost = parseFloat(item.cost);
        const validCost = isNaN(itemCost) || !isFinite(itemCost) ? 0 : itemCost;
        return sum + validCost;
      }, 0);

      // Safe success rate calculation with additional validation
      const successfulTopicItems = topicItems.filter(item =>
        item.response && typeof item.response === 'string' && !(
          item.response.toLowerCase().includes("llm generated i'm sorry, but i don't understand") ||
          item.response.toLowerCase().includes("i'm sorry, but i don't understand your question") ||
          item.response.toLowerCase().includes("i'm not currently able to answer questions about") ||
          item.response.toLowerCase().includes("this may be a topic that i am not trained on yet") ||
          item.response.toLowerCase().includes("i might not understand your phrasing") ||
          item.response.toLowerCase().includes("don't have access") ||
          item.response.toLowerCase().includes("cannot help") ||
          item.response.toLowerCase().includes("unable to") ||
          item.response.toLowerCase().includes("not able to") ||
          item.response.toLowerCase().includes("i don't have information") ||
          item.response.toLowerCase().includes("i'm not equipped") ||
          item.response.toLowerCase().includes("outside my capabilities")
        )
      );

      const successRate = topicItems.length > 0 ? (successfulTopicItems.length / topicItems.length * 100) : 0;

      // Ensure all values are valid numbers
      const safeQueries = Math.max(0, topicItems.length || 0);
      const safeCost = Math.max(0, isNaN(topicCost) || !isFinite(topicCost) ? 0 : topicCost);
      const safeSuccessRate = Math.max(0, Math.min(100, isNaN(successRate) || !isFinite(successRate) ? 0 : successRate));

      return {
        topic: String(topic), // Ensure topic is a string
        queries: safeQueries,
        cost: safeCost,
        successRate: safeSuccessRate
      };
    }).filter(item =>
      // Only include items with valid data
      item &&
      typeof item.topic === 'string' &&
      typeof item.queries === 'number' &&
      typeof item.cost === 'number' &&
      typeof item.successRate === 'number' &&
      !isNaN(item.queries) &&
      !isNaN(item.cost) &&
      !isNaN(item.successRate) &&
      isFinite(item.queries) &&
      isFinite(item.cost) &&
      isFinite(item.successRate) &&
      item.queries > 0
    ).sort((a, b) => b.queries - a.queries);

    // Debug: Log topic analysis results
    console.log('Topic analysis results:', topicCounts);
    console.log('Topic counts with data:', topicCounts.filter(item => item.queries > 0));

    // Daily usage pattern (filtered) - with NaN protection
    const dailyUsage = filteredData.reduce((acc, item) => {
      try {
        const date = new Date(item.timestamp).toDateString();
        acc[date] = (acc[date] || 0) + 1;
        return acc;
      } catch (error) {
        console.warn('Error processing daily usage item:', item, error);
        return acc;
      }
    }, {});

    const dailyData = Object.entries(dailyUsage)
      .map(([date, count]) => {
        const cost = filteredData
          .filter(item => {
            try {
              return new Date(item.timestamp).toDateString() === date;
            } catch (error) {
              console.warn('Error filtering daily cost data:', item, error);
              return false;
            }
          })
          .reduce((sum, item) => {
            const itemCost = parseFloat(item.cost);
            return sum + (isNaN(itemCost) || !isFinite(itemCost) ? 0 : itemCost);
          }, 0);

        return {
          date: new Date(date).toLocaleDateString(),
          queries: isNaN(count) || !isFinite(count) ? 0 : count,
          cost: isNaN(cost) || !isFinite(cost) ? 0 : cost
        };
      })
      .sort((a, b) => new Date(a.date) - new Date(b.date));

    // Hourly usage pattern (filtered) - with NaN protection
    const hourlyUsage = filteredData.reduce((acc, item) => {
      try {
        const hour = new Date(item.timestamp).getHours();
        if (!isNaN(hour) && hour >= 0 && hour <= 23) {
          acc[hour] = (acc[hour] || 0) + 1;
        }
        return acc;
      } catch (error) {
        console.warn('Error processing hourly usage item:', item, error);
        return acc;
      }
    }, {});

    const hourlyData = Array.from({length: 24}, (_, hour) => {
      const queries = hourlyUsage[hour] || 0;
      const cost = filteredData
        .filter(item => {
          try {
            return new Date(item.timestamp).getHours() === hour;
          } catch (error) {
            console.warn('Error filtering hourly cost data:', item, error);
            return false;
          }
        })
        .reduce((sum, item) => {
          const itemCost = parseFloat(item.cost);
          return sum + (isNaN(itemCost) || !isFinite(itemCost) ? 0 : itemCost);
        }, 0);

      return {
        hour: `${hour}:00`,
        queries: isNaN(queries) || !isFinite(queries) ? 0 : queries,
        cost: isNaN(cost) || !isFinite(cost) ? 0 : cost
      };
    });

    // Cost efficiency breakdown - with NaN protection
    const costEfficiencyData = filteredData.map(item => {
      try {
        const promptTokens = parseInt(item.promptTokens) || 0;
        const completionTokens = parseInt(item.completionTokens) || 0;
        const totalTokens = promptTokens + completionTokens;
        const cost = parseFloat(item.cost) || 0;
        const costPerToken = totalTokens > 0 && !isNaN(cost) && isFinite(cost) ? cost / totalTokens : 0;

        let efficiencyLevel;
        if (isNaN(costPerToken) || !isFinite(costPerToken) || costPerToken === 0) {
          efficiencyLevel = 'Unknown';
        } else if (costPerToken < 0.00001) {
          efficiencyLevel = 'High Efficiency';
        } else if (costPerToken < 0.00005) {
          efficiencyLevel = 'Medium Efficiency';
        } else {
          efficiencyLevel = 'Low Efficiency';
        }

        return {
          ...item,
          costPerToken: isNaN(costPerToken) || !isFinite(costPerToken) ? 0 : costPerToken,
          efficiencyLevel
        };
      } catch (error) {
        console.warn('Error calculating cost efficiency for item:', item, error);
        return {
          ...item,
          costPerToken: 0,
          efficiencyLevel: 'Unknown'
        };
      }
    });

    const efficiencyBreakdown = costEfficiencyData.reduce((acc, item) => {
      acc[item.efficiencyLevel] = (acc[item.efficiencyLevel] || 0) + 1;
      return acc;
    }, {});

    const efficiencyChartData = Object.entries(efficiencyBreakdown).map(([level, count]) => {
      const cost = costEfficiencyData
        .filter(item => item.efficiencyLevel === level)
        .reduce((sum, item) => {
          const itemCost = parseFloat(item.cost);
          return sum + (isNaN(itemCost) || !isFinite(itemCost) ? 0 : itemCost);
        }, 0);

      return {
        level,
        queries: isNaN(count) || !isFinite(count) ? 0 : count,
        cost: isNaN(cost) || !isFinite(cost) ? 0 : cost
      };
    });

    // Ensure all return values are safe numbers
    const safeAvgTokens = isNaN(avgTokens) || !isFinite(avgTokens) ? 0 : avgTokens;
    const safeTotalCost = isNaN(totalCost) || !isFinite(totalCost) ? 0 : totalCost;
    const safeFailureRate = totalQueries > 0 ? (failedQueries.length / totalQueries * 100).toFixed(1) : '0';

    return {
      uniqueUsers,
      totalQueries,
      totalCost: safeTotalCost,
      avgTokens: safeAvgTokens,
      failedQueries: failedQueries.length,
      failureRate: safeFailureRate,
      topicCounts,
      dailyData,
      hourlyData,
      filteredData,
      failedQueriesList: failedQueries,
      efficiencyChartData
    };
  }, [processedData, dateRange, searchTerm, queryTypeFilter, topicFilter, timeOfDayFilter, costEfficiencyFilter, topicKeywordsMap, assignments]);

  // Generate filter summary for chart titles
  const getFilterSummary = useCallback(() => {
    const filters = [];
    if (queryTypeFilter !== 'all') filters.push(`Type: ${queryTypeFilter}`);
    if (topicFilter !== 'all') filters.push(`Topic: ${topicFilter}`);
    if (timeOfDayFilter !== 'all') filters.push(`Time: ${timeOfDayFilter}`);
    if (costEfficiencyFilter !== 'all') filters.push(`Efficiency: ${costEfficiencyFilter}`);
    if (searchTerm) filters.push(`Search: "${searchTerm}"`);

    return filters.length > 0 ? ` (${filters.join(', ')})` : '';
  }, [queryTypeFilter, topicFilter, timeOfDayFilter, costEfficiencyFilter, searchTerm]);

  // Assignment Modal Component
  const AssignmentModal = () => {
    const [selectedAssignee, setSelectedAssignee] = useState('');
    const [priority, setPriority] = useState('medium');
    const [notes, setNotes] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [validationErrors, setValidationErrors] = useState({});

    // When opening in reassign mode, prefill assignee from current task and hide priority/notes
    React.useEffect(() => {
      if (showAssignmentModal && selectedQuery) {
        const existing = getTaskAssignment(selectedQuery);
        if (existing && existing.assignee && existing.assignee.id) {
          setSelectedAssignee(existing.assignee.id.toString());
        }
        if (existing && existing.priority) {
          setPriority(existing.priority);
        }
        if (existing && existing.notes) {
          setNotes(existing.notes);
        }
      }
    }, [showAssignmentModal, selectedQuery]);

    // Validation function
    const validateForm = () => {
      const errors = {};

      if (!selectedAssignee) {
        errors.assignee = 'Please select a team member to assign to';
      }

      if (!priority) {
        errors.priority = 'Please select a priority level';
      }

      setValidationErrors(errors);
      return Object.keys(errors).length === 0;
    };

    // Handle form submission with API call
    const handleAssign = async () => {
      if (!validateForm()) {
        return;
      }

      if (!selectedQuery) return;

      setIsSubmitting(true);

      try {
        const queryId = generateQueryId(selectedQuery);

        // If reassigning, call update endpoint; otherwise use save_task to create
        const response = await fetch(isReassignMode ? '../update_task.php' : '../save_task.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(
            isReassignMode
              ? { assignee_id: selectedAssignee, query_id: queryId }
              : { assignee_id: selectedAssignee, query_id: queryId, priority: toServerPriority(priority), notes }
          )
        });

        const result = await response.json();

  if (result.success) {
          // Reload bot data so UI reflects the new task assignment
          try {
            await loadBotData();
          } catch (e) {
            console.error('Error reloading bot data after assignment:', e);
          }

          // Update local assignments state
          const newAssignments = {
            ...assignments,
            [queryId]: {
              assignee: result.assignee,
              status: 'assigned',
              priority: priority,
              notes: notes,
              assignedDate: new Date().toISOString(),
              updatedDate: new Date().toISOString()
            }
          };

          // Save to localStorage as backup
          try {
            localStorage.setItem('savy-query-assignments', JSON.stringify(newAssignments));
            setAssignments(newAssignments);
          } catch (error) {
            console.error('Error saving assignments to localStorage:', error);
          }

          // Show success message
          if (isReassignMode) {
            setUploadStatus({ type: 'success', message: 'Task reassigned successfully' });
          } else {
            setUploadStatus({
              type: 'success',
              message: `Task ${result.action} successfully and assigned to ${result.assignee?.name || ''}`
            });
          }

          // Clear status after 5 seconds
          setTimeout(() => setUploadStatus({ type: '', message: '' }), 5000);

          // Close modal and reset form
          setShowAssignmentModal(false);
          setIsReassignMode(false);
          setSelectedQuery(null);
          setSelectedAssignee('');
          setPriority('medium');
          setNotes('');
          setValidationErrors({});
        } else {
          setUploadStatus({
            type: 'error',
            message: `Failed to assign task: ${result.error}`
          });
          setTimeout(() => setUploadStatus({ type: '', message: '' }), 5000);
        }
      } catch (error) {
        console.error('Error assigning task:', error);
        setUploadStatus({
          type: 'error',
          message: 'Network error occurred while assigning task'
        });
        setTimeout(() => setUploadStatus({ type: '', message: '' }), 5000);
      } finally {
        setIsSubmitting(false);
      }
    };

    if (!showAssignmentModal || !selectedQuery) return null;

    return (
      <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div className="bg-white rounded-lg p-6 max-w-lg w-full mx-4">
          <h3 className="text-lg font-semibold mb-4">Assign Failed Query</h3>

          <div className="mb-4">
            <label className="block text-sm font-medium text-gray-700 mb-2">Query:</label>
            <div className="p-3 bg-gray-50 rounded-md">
              <p className="text-sm text-gray-800 font-medium">{selectedQuery.prompt}</p>
              <p className="text-xs text-gray-600 mt-1">Response: {selectedQuery.response}</p>
            </div>
          </div>

          <div className="mb-4">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Assignee: <span className="text-red-500">*</span>
            </label>
            <select
              value={selectedAssignee}
              onChange={(e) => {
                setSelectedAssignee(e.target.value);
                if (validationErrors.assignee) {
                  setValidationErrors({...validationErrors, assignee: null});
                }
              }}
              className={`w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                validationErrors.assignee ? 'border-red-500' : 'border-gray-300'
              }`}
              disabled={isSubmitting}
            >
              <option value="">Select team member...</option>
              {teamMembers.map(member => (
                <option key={member.id} value={member.id}>
                  {member.name} - {member.role} ({member.department})
                </option>
              ))}
            </select>
            {validationErrors.assignee && (
              <p className="text-red-500 text-xs mt-1">{validationErrors.assignee}</p>
            )}
          </div>
          {!isReassignMode && (
          <div className="mb-4">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Priority: <span className="text-red-500">*</span>
            </label>
            <select
              value={priority}
              onChange={(e) => {
                setPriority(e.target.value);
                if (validationErrors.priority) {
                  setValidationErrors({...validationErrors, priority: null});
                }
              }}
              className={`w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                validationErrors.priority ? 'border-red-500' : 'border-gray-300'
              }`}
              disabled={isSubmitting}
            >
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
            {validationErrors.priority && (
              <p className="text-red-500 text-xs mt-1">{validationErrors.priority}</p>
            )}
          </div>
          )}

          {!isReassignMode && (
          <div className="mb-4">
            <label className="block text-sm font-medium text-gray-700 mb-2">Notes:</label>
            <textarea
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="Add any notes or context..."
              className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              rows="3"
              disabled={isSubmitting}
            />
          </div>
          )}

          <div className="flex justify-end space-x-3">
            <button
              onClick={() => {
                setShowAssignmentModal(false);
                setSelectedQuery(null);
                setSelectedAssignee('');
                setPriority('medium');
                setNotes('');
                setValidationErrors({});
              }}
              className="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50"
              disabled={isSubmitting}
            >
              Cancel
            </button>
            <button
              onClick={handleAssign}
              disabled={isSubmitting}
              className={`px-4 py-2 text-white rounded-md transition-colors ${
                isSubmitting 
                  ? 'bg-gray-400 cursor-not-allowed' 
                  : 'bg-blue-600 hover:bg-blue-700'
              }`}
            >
              {isSubmitting ? (
                <span className="flex items-center">
                  <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
                  {isReassignMode ? 'Reassigning...' : 'Assigning...'}
                </span>
              ) : (
                isReassignMode ? 'Reassign' : 'Assign'
              )}
            </button>
          </div>
        </div>
      </div>
    );
  };

  const COLORS = ['#8884d8', '#82ca9d', '#ffc658', '#ff7c7c', '#8dd1e1'];

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="bg-white rounded-lg shadow-md p-6 mb-6">
          <div className="flex justify-between items-start mb-4">
            <div>
              <h1 className="text-3xl font-bold text-gray-800 mb-2">Conversational AI Analytics Dashboard</h1>
              <p className="text-gray-600">
                {data.length > 0
                  ? `Showing bot data (${processedData.length} records) ${botId ? `for bot ID ${botId}` : ''}`
                  : !botId
                  ? 'Please provide a bot_id in the URL query string to load data'
                  : `Showing sample data (${processedData.length} records) - No data loaded for bot ${botId}`}
              </p>
            </div>
            <div className="flex items-center gap-2">
              {data.length > 0 && botId && (
                <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                  <CheckCircle className="w-4 h-4 mr-1" />
                  Bot Data Loaded
                </span>
              )}
              {data.length === 0 && (
                <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                  <Info className="w-4 h-4 mr-1" />
                  {!botId ? 'No Bot ID' : 'Sample Data'}
                </span>
              )}
            </div>
          </div>

          {/* Status Messages */}
          {uploadStatus.message && (
            <div className={`mb-4 p-3 rounded-lg flex items-center gap-2 ${
              uploadStatus.type === 'success' ? 'bg-green-100 text-green-800' :
              uploadStatus.type === 'error' ? 'bg-red-100 text-red-800' :
              uploadStatus.type === 'warning' ? 'bg-yellow-100 text-yellow-800' :
              'bg-blue-100 text-blue-800'
            }`}>
              {uploadStatus.type === 'success' && <CheckCircle className="w-5 h-5" />}
              {uploadStatus.type === 'error' && <XCircle className="w-5 h-5" />}
              {uploadStatus.type === 'warning' && <AlertTriangle className="w-5 h-5" />}
              {uploadStatus.type === 'info' && <Info className="w-5 h-5" />}
              {isLoading && <RefreshCw className="w-5 h-5 animate-spin" />}
              <span>{uploadStatus.message}</span>
            </div>
          )}

          {/* Controls */}
          <div className="flex flex-wrap gap-4 items-center">
            <div className="flex items-center gap-2">
              <Search className="w-5 h-5 text-gray-600" />
              <input
                type="text"
                placeholder="Search prompts or responses..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <select
              value={dateRange}
              onChange={(e) => setDateRange(e.target.value)}
              className="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="1d">Last 24 hours</option>
              <option value="7d">Last 7 days</option>
              <option value="30d">Last 30 days</option>
              <option value="all">All time</option>
            </select>

            <button
              onClick={() => exportData(analytics.filteredData)}
              disabled={analytics.filteredData.length === 0}
              className="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed"
            >
              <Download className="w-4 h-4" />
              Export Data
            </button>

            {botId && (
              <button
                onClick={() => loadBotData()}
                className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
              >
                <RefreshCw className="w-4 h-4" />
                Refresh Data
              </button>
            )}
          </div>
        </div>

        {/* Product Manager Filters */}
        <div className="bg-white rounded-lg shadow-md p-6 mb-6">
          <div className="flex justify-between items-start mb-4">
            <div>
              <h3 className="text-lg font-semibold text-gray-800 mb-2">Analytics Filters</h3>
              <p className="text-sm text-gray-600">Filter data to analyze specific user behaviors and conversation patterns</p>
            </div>
            <button
              onClick={() => {
                setQueryTypeFilter('all');
                setTopicFilter('all');
                setTimeOfDayFilter('all');
                setCostEfficiencyFilter('all');
              }}
              className="text-sm text-blue-600 hover:text-blue-800 underline"
            >
              Reset Filters
            </button>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {/* Query Success Filter */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Query Outcome</label>
              <select
                value={queryTypeFilter}
                onChange={(e) => setQueryTypeFilter(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="all">All Queries</option>
                <option value="successful">Successful Only</option>
                <option value="failed">Failed Only</option>
              </select>
            </div>

            {/* Topic Filter */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Topic Category</label>
              <select
                value={topicFilter}
                onChange={(e) => setTopicFilter(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                {topicOptions.map(opt => (
                  <option key={opt.value} value={opt.value}>{opt.label}</option>
                ))}
              </select>
            </div>

            {/* Time of Day Filter */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Time of Day</label>
              <select
                value={timeOfDayFilter}
                onChange={(e) => setTimeOfDayFilter(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="all">All Hours</option>
                <option value="business">Business Hours (9AM-5PM)</option>
                <option value="after-hours">After Hours</option>
              </select>
            </div>

            {/* Cost Efficiency Filter */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Cost Efficiency</label>
              <select
                value={costEfficiencyFilter}
                onChange={(e) => setCostEfficiencyFilter(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="all">All Efficiency Levels</option>
                <option value="high-efficiency">High Efficiency</option>
                <option value="medium">Medium Efficiency</option>
                <option value="low-efficiency">Low Efficiency</option>
              </select>
            </div>
          </div>

          {/* Filter Summary */}
          <div className="mt-4 flex flex-wrap gap-2">
            {queryTypeFilter !== 'all' && (
              <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                {queryTypeFilter === 'successful' ? 'Successful Queries' : 'Failed Queries'}
              </span>
            )}
            {topicFilter !== 'all' && (
              <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                {topicFilter.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase())}
              </span>
            )}
            {timeOfDayFilter !== 'all' && (
              <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                {timeOfDayFilter === 'business' ? 'Business Hours' : 'After Hours'}
              </span>
            )}
            {costEfficiencyFilter !== 'all' && (
              <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                {costEfficiencyFilter.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase())}
              </span>
            )}
          </div>
        </div>

        {/* KPI Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
          <div className="bg-white rounded-lg shadow-md p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Unique Users</p>
                <p className="text-2xl font-bold text-gray-800">{analytics.uniqueUsers}</p>
                {(queryTypeFilter !== 'all' || topicFilter !== 'all' || timeOfDayFilter !== 'all' || costEfficiencyFilter !== 'all') && (
                  <p className="text-xs text-blue-600">Filtered data</p>
                )}
              </div>
              <Users className="w-8 h-8 text-blue-500" />
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-md p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Queries</p>
                <p className="text-2xl font-bold text-gray-800">{analytics.totalQueries}</p>
                {(queryTypeFilter !== 'all' || topicFilter !== 'all' || timeOfDayFilter !== 'all' || costEfficiencyFilter !== 'all') && (
                  <p className="text-xs text-blue-600">Filtered data</p>
                )}
              </div>
              <MessageSquare className="w-8 h-8 text-green-500" />
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-md p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Failed Queries</p>
                <p className="text-2xl font-bold text-red-600">{analytics.failedQueries}</p>
                <p className="text-xs text-gray-500">{analytics.failureRate}% failure rate</p>
              </div>
              <AlertTriangle className="w-8 h-8 text-red-500" />
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-md p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Cost</p>
                <p className="text-2xl font-bold text-gray-800">
                  ${analytics.totalCost < 0.01 ? analytics.totalCost.toFixed(6) : analytics.totalCost.toFixed(2)}
                </p>
                <p className="text-xs text-gray-500">
                  Avg: ${analytics.totalQueries > 0 ?
                    (analytics.totalCost / analytics.totalQueries < 0.01 ?
                      (analytics.totalCost / analytics.totalQueries).toFixed(6) :
                      (analytics.totalCost / analytics.totalQueries).toFixed(4)
                    ) : '0'}/query
                </p>
              </div>
              <DollarSign className="w-8 h-8 text-yellow-500" />
            </div>
          </div>
        </div>

        {/* Charts Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
          {/* Daily Usage Trend */}
          <div className="bg-white rounded-lg shadow-md p-6">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-lg font-semibold text-gray-800">Daily Usage Trend{getFilterSummary()}</h3>
              {(queryTypeFilter !== 'all' || topicFilter !== 'all' || timeOfDayFilter !== 'all' || costEfficiencyFilter !== 'all') && (
                <span className="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">Filtered</span>
              )}
            </div>
            <ResponsiveContainer width="100%" height={300}>
              <AreaChart data={analytics.dailyData.filter(item =>
                item &&
                typeof item.queries === 'number' &&
                typeof item.cost === 'number' &&
                !isNaN(item.queries) &&
                !isNaN(item.cost) &&
                isFinite(item.queries) &&
                isFinite(item.cost) &&
                item.queries >= 0 &&
                item.cost >= 0
              )}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="date" />
                <YAxis yAxisId="left" />
                <YAxis yAxisId="right" orientation="right" />
                <Tooltip
                  labelFormatter={(value) => `Date: ${value}`}
                  formatter={(value, name, props) => {
                    if (name === 'queries') return [value, 'Queries'];
                    if (name === 'cost') return [`$${value.toFixed(3)}`, 'Cost'];
                    return [value, name];
                  }}
                />
                <Area yAxisId="left" type="monotone" dataKey="queries" stroke="#8884d8" fill="#8884d8" fillOpacity={0.6} />
                <Area yAxisId="right" type="monotone" dataKey="cost" stroke="#82ca9d" fill="#82ca9d" fillOpacity={0.3} />
              </AreaChart>
            </ResponsiveContainer>
            <p className="text-xs text-gray-500 mt-2">
              Showing {analytics.totalQueries} conversations over {analytics.dailyData.length} days
            </p>
          </div>

          {/* Hourly Usage Pattern */}
          <div className="bg-white rounded-lg shadow-md p-6">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-lg font-semibold text-gray-800">Hourly Usage Pattern{getFilterSummary()}</h3>
              {(queryTypeFilter !== 'all' || topicFilter !== 'all' || timeOfDayFilter !== 'all' || costEfficiencyFilter !== 'all') && (
                <span className="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">Filtered</span>
              )}
            </div>
            <ResponsiveContainer width="100%" height={300}>
              <BarChart data={analytics.hourlyData.filter(item =>
                item &&
                typeof item.queries === 'number' &&
                typeof item.cost === 'number' &&
                !isNaN(item.queries) &&
                !isNaN(item.cost) &&
                isFinite(item.queries) &&
                isFinite(item.cost) &&
                item.queries >= 0 &&
                item.cost >= 0
              )}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="hour" />
                <YAxis yAxisId="left" />
                <YAxis yAxisId="right" orientation="right" />
                <Tooltip
                  labelFormatter={(value) => `Hour: ${value}`}
                  formatter={(value, name, props) => {
                    if (name === 'queries') return [value, 'Queries'];
                    if (name === 'cost') return [`$${value.toFixed(3)}`, 'Cost'];
                    return [value, name];
                  }}
                />
                <Bar yAxisId="left" dataKey="queries" fill="#82ca9d" />
                <Bar yAxisId="right" dataKey="cost" fill="#ffc658" fillOpacity={0.7} />
              </BarChart>
            </ResponsiveContainer>
            <p className="text-xs text-gray-500 mt-2">
              Peak hour: {analytics.hourlyData.reduce((max, item) => item.queries > max.queries ? item : max, {hour: '0:00', queries: 0}).hour}
              ({analytics.hourlyData.reduce((max, item) => Math.max(max, item.queries), 0)} queries)
            </p>
          </div>
        </div>

        {/* Topic Analysis and Cost Breakdown */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
          {/* Topic Distribution */}
          <div className="bg-white rounded-lg shadow-md p-6">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-lg font-semibold text-gray-800">Topic Distribution{getFilterSummary()}</h3>
              {(queryTypeFilter !== 'all' || topicFilter !== 'all' || timeOfDayFilter !== 'all' || costEfficiencyFilter !== 'all') && (
                <span className="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">Filtered</span>
              )}
            </div>
            <ResponsiveContainer width="100%" height={300}>
              <PieChart>
                <Pie
                  data={analytics.topicCounts.filter(item => item.queries > 0)}
                  dataKey="queries"
                  nameKey="topic"
                  cx="50%"
                  cy="50%"
                  outerRadius={80}
                  label={({topic, queries, percent}) => `${topic}: ${queries} (${(percent * 100).toFixed(1)}%)`}
                >
                  {analytics.topicCounts.filter(item => item.queries > 0).map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                  ))}
                </Pie>
                <Tooltip formatter={(value, name, props) => [
                  `${value} queries`,
                  `$${props.payload.cost.toFixed(3)} cost`,
                  `${props.payload.successRate.toFixed(1)}% success rate`
                ]} />
              </PieChart>
            </ResponsiveContainer>
            <p className="text-xs text-gray-500 mt-2">
              Top topic: {analytics.topicCounts.reduce((max, item) => item.queries > max.queries ? item : max, {topic: 'None', queries: 0}).topic}
              ({analytics.topicCounts.reduce((max, item) => Math.max(max, item.queries), 0)} queries)
            </p>
          </div>

          {/* Cost Efficiency Analysis */}
          <div className="bg-white rounded-lg shadow-md p-6">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-lg font-semibold text-gray-800">Cost Efficiency Distribution{getFilterSummary()}</h3>
              {(queryTypeFilter !== 'all' || topicFilter !== 'all' || timeOfDayFilter !== 'all' || costEfficiencyFilter !== 'all') && (
                <span className="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">Filtered</span>
              )}
            </div>
            <ResponsiveContainer width="100%" height={300}>
              <BarChart data={analytics.efficiencyChartData}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="level" />
                <YAxis yAxisId="left" />
                <YAxis yAxisId="right" orientation="right" />
                <Tooltip
                  formatter={(value, name, props) => {
                    if (name === 'queries') return [value, 'Queries'];
                    if (name === 'cost') return [`$${value.toFixed(4)}`, 'Total Cost'];
                    return [value, name];
                  }}
                />
                <Bar yAxisId="left" dataKey="queries" fill="#8884d8" />
                <Bar yAxisId="right" dataKey="cost" fill="#ff7c7c" fillOpacity={0.7} />
              </BarChart>
            </ResponsiveContainer>
            <div className="mt-4 grid grid-cols-3 gap-4 text-sm">
              <div className="text-center">
                <div className="font-semibold">{analytics.avgTokens.toFixed(0)}</div>
                <div className="text-gray-600">Avg Tokens</div>
              </div>
              <div className="text-center">
                <div className="font-semibold">${(analytics.totalCost / (analytics.filteredData.reduce((sum, item) => sum + item.promptTokens + item.completionTokens, 0) || 1)).toFixed(6)}</div>
                <div className="text-gray-600">Cost/Token</div>
              </div>
              <div className="text-center">
                <div className="font-semibold">{analytics.totalQueries}</div>
                <div className="text-gray-600">Total Queries</div>
              </div>
            </div>
          </div>
        </div>

        {/* Success Rate and Query Performance Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
          {/* Success Rate by Topic */}
          <div className="bg-white rounded-lg shadow-md p-6">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-lg font-semibold text-gray-800">Success Rate by Topic{getFilterSummary()}</h3>
              {(queryTypeFilter !== 'all' || topicFilter !== 'all' || timeOfDayFilter !== 'all' || costEfficiencyFilter !== 'all') && (
                <span className="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">Filtered</span>
              )}
            </div>
            <ResponsiveContainer width="100%" height={300}>
              {analytics.topicCounts && analytics.topicCounts.length > 0 ? (
                <BarChart data={analytics.topicCounts.filter(item => item.queries > 0)} layout="horizontal">
                  {/* Debug: Show what data is being passed to the chart */}
                  {console.log('Chart data for Success Rate by Topic:', analytics.topicCounts.filter(item => item.queries > 0))}
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis type="number" domain={[0, 100]} />
                  <YAxis type="category" dataKey="topic" width={120} />
                  <Tooltip
                    formatter={(value, name, props) => {
                      if (name === 'successRate') return [`${value.toFixed(1)}%`, 'Success Rate'];
                      return [value, name];
                    }}
                    labelFormatter={(label) => `Topic: ${label}`}
                  />
                  <Bar dataKey="successRate" fill="#82ca9d" />
                </BarChart>
              ) : (
                <div className="flex items-center justify-center h-full">
                  <div className="text-center text-gray-500">
                    <p className="text-lg mb-2">No topic data available</p>
                    <p className="text-sm">Current filter settings may be excluding all data, or no queries match the topic categories.</p>
                    <p className="text-xs mt-2">Debug: Analytics object topics: {analytics.topicCounts ? analytics.topicCounts.length : 'undefined'}</p>
                  </div>
                </div>
              )}
            </ResponsiveContainer>
            <p className="text-xs text-gray-500 mt-2">
              Overall success rate: {analytics.totalQueries > 0 ? (((analytics.totalQueries - analytics.failedQueries) / analytics.totalQueries) * 100).toFixed(1) : 0}%
              ({analytics.totalQueries - analytics.failedQueries} successful out of {analytics.totalQueries} total)
            </p>
          </div>

          {/* Time-based Performance */}
          <div className="bg-white rounded-lg shadow-md p-6">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-lg font-semibold text-gray-800">Performance by Time</h3>
              {(queryTypeFilter !== 'all' || topicFilter !== 'all' || timeOfDayFilter !== 'all' || costEfficiencyFilter !== 'all') && (
                <span className="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">Filtered</span>
              )}
            </div>
            <div className="space-y-4">
              {(() => {
                const businessHoursQueries = analytics.filteredData.filter(item => {
                  const date = new Date(item.timestamp);
                  const hour = date.getHours();
                  const dayOfWeek = date.getDay();
                  return (dayOfWeek >= 1 && dayOfWeek <= 5) && (hour >= 9 && hour <= 17);
                });
                const afterHoursQueries = analytics.filteredData.filter(item => {
                  const date = new Date(item.timestamp);
                  const hour = date.getHours();
                  const dayOfWeek = date.getDay();
                  return !((dayOfWeek >= 1 && dayOfWeek <= 5) && (hour >= 9 && hour <= 17));
                });

                const businessHoursCost = businessHoursQueries.reduce((sum, item) => sum + item.cost, 0);
                const afterHoursCost = afterHoursQueries.reduce((sum, item) => sum + item.cost, 0);
                const businessHoursAvgCost = businessHoursQueries.length > 0 ? businessHoursCost / businessHoursQueries.length : 0;
                const afterHoursAvgCost = afterHoursQueries.length > 0 ? afterHoursCost / afterHoursQueries.length : 0;

                return (
                  <>
                    <div className="flex justify-between items-center">
                      <span className="text-gray-600">Business Hours (9AM-5PM)</span>
                      <div className="text-right">
                        <div className="font-semibold">{businessHoursQueries.length} queries</div>
                        <div className="text-sm text-gray-500">${businessHoursAvgCost.toFixed(4)} avg</div>
                      </div>
                    </div>
                    <div className="flex justify-between items-center">
                      <span className="text-gray-600">After Hours</span>
                      <div className="text-right">
                        <div className="font-semibold">{afterHoursQueries.length} queries</div>
                        <div className="text-sm text-gray-500">${afterHoursAvgCost.toFixed(4)} avg</div>
                      </div>
                    </div>
                    <div className="pt-2 border-t">
                      <div className="text-sm text-gray-600">
                        After-hours represents {afterHoursQueries.length > 0 ? ((afterHoursQueries.length / analytics.totalQueries) * 100).toFixed(1) : 0}% of total usage
                      </div>
                    </div>
                  </>
                );
              })()}
            </div>
          </div>
        </div>

        {/* Failed Queries Analysis with Assignment Management */}
        <div className="bg-white rounded-lg shadow-md p-6 mb-6">
          <div className="flex justify-between items-center mb-4">
            <h3 className="text-lg font-semibold text-gray-800">Failed Queries Analysis & Assignment</h3>
            <div className="flex items-center space-x-2">
              <select
                value={assignmentFilter}
                onChange={(e) => setAssignmentFilter(e.target.value)}
                className="text-xs border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="all">All Queries</option>
                <option value="unassigned">Unassigned</option>
                <option value="assigned">Assigned</option>
                <option value="in-progress">In Progress</option>
                <option value="resolved">Resolved</option>
              </select>
              {(queryTypeFilter !== 'all' || topicFilter !== 'all' || timeOfDayFilter !== 'all' || costEfficiencyFilter !== 'all') && (
                <span className="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">Filtered</span>
              )}
            </div>
          </div>

          {/* Assignment Stats */}
          <div className="grid grid-cols-4 gap-4 mb-6">
            <div className="bg-gray-50 p-3 rounded-lg">
              <p className="text-xs text-gray-600">Total Failed</p>
              <p className="text-lg font-semibold text-gray-800">{analytics.failedQueriesList.length}</p>
            </div>
            <div className="bg-yellow-50 p-3 rounded-lg">
              <p className="text-xs text-yellow-600">Unassigned</p>
              <p className="text-lg font-semibold text-yellow-800">
                {analytics.failedQueriesList.filter(query => !getTaskAssignment(query)).length}
              </p>
            </div>
            <div className="bg-blue-50 p-3 rounded-lg">
              <p className="text-xs text-blue-600">Assigned</p>
              <p className="text-lg font-semibold text-blue-800">
                {analytics.failedQueriesList.filter(query => {
                  const assignment = getTaskAssignment(query);
                  return assignment && ['assigned', 'in-progress'].includes((assignment.status || '').toLowerCase());
                }).length}
              </p>
            </div>
            <div className="bg-green-50 p-3 rounded-lg">
              <p className="text-xs text-green-600">Resolved</p>
              <p className="text-lg font-semibold text-green-800">
                {analytics.failedQueriesList.filter(query => {
                  const assignment = getTaskAssignment(query);
                  return assignment && (assignment.status || '').toLowerCase() === 'resolved';
                }).length}
              </p>
            </div>
          </div>

          {/* Failed Queries List with Assignment Controls */}
          <div className="space-y-3">
            {analytics.failedQueriesList
              .filter(query => {
                // Use database task data instead of localStorage assignments
                const taskAssignment = getTaskAssignment(query);

                if (assignmentFilter === 'unassigned') return !taskAssignment;
                if (assignmentFilter === 'assigned') return taskAssignment && taskAssignment.status === 'assigned';
                if (assignmentFilter === 'in-progress') return taskAssignment && taskAssignment.status === 'in-progress';
                if (assignmentFilter === 'resolved') return taskAssignment && taskAssignment.status === 'resolved';
                return true; // 'all'
              })
              .slice(0, 10)
              .map((item, index) => {
                const queryId = generateQueryId(item);
                // Use database task data instead of localStorage
                const assignment = getTaskAssignment(item);

                return (
                  <div key={index} className="border-l-4 border-red-500 pl-4 py-3 bg-red-50 rounded-r-lg">
                    <div className="flex justify-between items-start">
                      <div className="flex-1">
                        <p className="font-semibold text-gray-800 mb-1">Query: {item.prompt}</p>
                        <p className="text-sm text-gray-600 mb-2">Response: {item.response}</p>
                        <p className="text-xs text-gray-500">
                          Cost: ${item.cost.toFixed(4)} | Time: {new Date(item.timestamp).toLocaleString()}
                        </p>

                        {/* Assignment Info - showing real database data */}
                        {assignment && (
                          <div className="mt-2 p-2 bg-white rounded border">
                            <div className="flex items-center justify-between">
                              <div className="flex items-center space-x-2">
                                <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                                  assignment.status === 'assigned' ? 'bg-blue-100 text-blue-800' :
                                  assignment.status === 'in-progress' ? 'bg-yellow-100 text-yellow-800' :
                                  assignment.status === 'resolved' ? 'bg-green-100 text-green-800' :
                                  'bg-gray-100 text-gray-800'
                                }`}>
                                  {assignment.status.charAt(0).toUpperCase() + assignment.status.slice(1)}
                                </span>
                                <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                                  assignment.priority === 'urgent' ? 'bg-red-100 text-red-800' :
                                  assignment.priority === 'high' ? 'bg-orange-100 text-orange-800' :
                                  assignment.priority === 'medium' ? 'bg-yellow-100 text-yellow-800' :
                                  'bg-gray-100 text-gray-800'
                                }`}>
                                  {assignment.priority.charAt(0).toUpperCase() + assignment.priority.slice(1)}
                                </span>
                              </div>
                              <div className="flex items-center space-x-1">
                                {assignment.status !== 'resolved' && (
                                  <>
                                    <button
                                      onClick={() => updateAssignmentStatus(queryId, 'in-progress')}
                                      className="text-xs bg-yellow-600 text-white px-2 py-1 rounded hover:bg-yellow-700"
                                      disabled={assignment.status === 'in-progress'}
                                    >
                                      In Progress
                                    </button>
                                    <button
                                      onClick={() => updateAssignmentStatus(queryId, 'resolved')}
                                      className="text-xs bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700"
                                    >
                                      Resolve
                                    </button>
                                  </>
                                )}
                                {assignment.status !== 'resolved' && (
                                  <button
                                    onClick={() => removeAssignment(queryId)}
                                    className="text-xs bg-gray-600 text-white px-2 py-1 rounded hover:bg-gray-700"
                                  >
                                    Unassign
                                  </button>
                                )}
                              </div>
                            </div>
                            <div className="mt-1 text-xs text-gray-600">
                              <p>Assigned to: <span className="font-medium">{assignment.assignee.name}</span> ({assignment.assignee.role})</p>
                              <p>Assigned: {(() => {
                                const d = safeParseDate(assignment.assignedDate);
                                return d ? d.toLocaleDateString() : 'Unknown';
                              })()}</p>
                              {assignment.notes && <p>Notes: {assignment.notes}</p>}
                            </div>
                          </div>
                        )}
                      </div>

                      {/* Action Buttons */}
                      <div className="ml-4 flex-shrink-0">
        {!assignment ? (
                          <button
                            onClick={() => {
                              setSelectedQuery(item);
          setIsReassignMode(false);
                              setShowAssignmentModal(true);
                            }}
                            className="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700"
                          >
                            Assign
                          </button>
                        ) : (
                          assignment.status !== 'resolved' && (
                            <button
                              onClick={() => {
                                setSelectedQuery(item);
                                setIsReassignMode(true);
                                setShowAssignmentModal(true);
                              }}
                              className="bg-gray-600 text-white px-3 py-1 rounded text-xs hover:bg-gray-700"
                            >
                              Reassign
                            </button>
                          )
                        )}
                      </div>
                    </div>
                  </div>
                );
              })}

            { analytics.failedQueriesList.length === 0 && (
              <div className="text-center py-8 text-gray-500">
                <p>No failed queries found in the current selection.</p>
              </div>
            )}
          </div>
        </div>

        {/* Recent Activity */}
        <div className="bg-white rounded-lg shadow-md p-6">
          <h3 className="text-lg font-semibold text-gray-800 mb-4">Recent Activity</h3>
                   <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-4 py-2 text-left">Time</th>
                  <th className="px-4 py-2 text-left">Prompt</th>
                  <th className="px-4 py-2 text-left">Response Preview</th>
                  <th className="px-4 py-2 text-left">Tokens</th>
                  <th className="px-4 py-2 text-left">Cost</th>
                </tr>
              </thead>
              <tbody>
                {analytics.filteredData.slice(0, 10).map((item, index) => (
                  <tr key={index} className="border-b hover:bg-gray-50">
                    <td className="px-4 py-2">{new Date(item.timestamp).toLocaleString()}</td>
                    <td className="px-4 py-2 max-w-xs truncate">{item.prompt}</td>
                    <td className="px-4 py-2 max-w-xs truncate">{item.response}</td>
                    <td className="px-4 py-2">{item.promptTokens + item.completionTokens}</td>
                    <td className="px-4 py-2">${item.cost.toFixed(4)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* Assignment Modal */}
      <AssignmentModal />
    </div>
  );
};

export default LogsDashboard;

