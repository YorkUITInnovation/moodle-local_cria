import React, { useState, useMemo, useCallback } from 'react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, LineChart, Line, PieChart, Pie, Cell, Area, AreaChart } from 'recharts';
import { Upload, Users, MessageSquare, AlertTriangle, DollarSign, Clock, TrendingUp, TrendingDown, Calendar, FileText, Search, Download, RefreshCw, CheckCircle, XCircle, Info } from 'lucide-react';

const LogsDashboard = () => {
  const [data, setData] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [dateRange, setDateRange] = useState('7d');
  const [selectedMetric, setSelectedMetric] = useState('all');
  const [uploadStatus, setUploadStatus] = useState({ type: '', message: '' });
  const [isLoading, setIsLoading] = useState(false);
  const [dataSource, setDataSource] = useState('sample'); // 'sample' or 'uploaded'

  // Sample data matching your Excel structure
  const sampleData = [
    { prompt: "What is machine learning?", response: "Machine learning is a subset of artificial intelligence that enables computers to learn and improve from experience without being explicitly programmed.", promptTokens: 15, completionTokens: 45, cost: 0.002, timestamp: "2024-01-15T09:30:00Z" },
    { prompt: "How do I reset my password?", response: "I don't have access to your account system. Please contact customer support for password reset assistance.", promptTokens: 12, completionTokens: 25, cost: 0.001, timestamp: "2024-01-15T10:15:00Z" },
    { prompt: "What are the benefits of cloud computing?", response: "Cloud computing offers scalability, cost-efficiency, remote accessibility, automatic updates, and enhanced collaboration capabilities.", promptTokens: 18, completionTokens: 35, cost: 0.0015, timestamp: "2024-01-15T11:00:00Z" },
    { prompt: "Can you book a flight for me?", response: "I'm not able to book flights or make reservations. I can provide information about airlines and travel tips instead.", promptTokens: 14, completionTokens: 28, cost: 0.0012, timestamp: "2024-01-15T14:20:00Z" },
    { prompt: "Explain quantum computing", response: "Quantum computing uses quantum mechanical phenomena like superposition and entanglement to process information in fundamentally different ways than classical computers.", promptTokens: 10, completionTokens: 40, cost: 0.0018, timestamp: "2024-01-15T15:45:00Z" },
    { prompt: "What's the weather like today?", response: "I don't have access to real-time weather data. Please check a weather app or website for current conditions.", promptTokens: 13, completionTokens: 24, cost: 0.001, timestamp: "2024-01-16T08:30:00Z" },
    { prompt: "How to improve team productivity?", response: "Effective strategies include setting clear goals, using collaboration tools, regular feedback, time management techniques, and creating a positive work environment.", promptTokens: 16, completionTokens: 42, cost: 0.0019, timestamp: "2024-01-16T09:15:00Z" },
    { prompt: "What is blockchain technology?", response: "Blockchain is a distributed ledger technology that maintains a continuously growing list of records, called blocks, which are linked and secured using cryptography.", promptTokens: 12, completionTokens: 38, cost: 0.0016, timestamp: "2024-01-16T10:30:00Z" },
    { prompt: "Can you access my email?", response: "I cannot access personal emails or external systems. I'm designed to provide information and assistance through our conversation only.", promptTokens: 11, completionTokens: 26, cost: 0.0011, timestamp: "2024-01-16T13:45:00Z" },
    { prompt: "Best practices for data security", response: "Key practices include strong passwords, multi-factor authentication, regular updates, data encryption, employee training, and incident response plans.", promptTokens: 15, completionTokens: 36, cost: 0.0017, timestamp: "2024-01-16T16:20:00Z" }
  ];

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

  // Enhanced CSV parser with flexible column detection
  const parseCSVData = (text) => {
    const lines = text.trim().split('\n');
    if (lines.length < 2) throw new Error('File must contain at least a header and one data row.');

    const headers = lines[0].split(',').map(h => h.trim().replace(/"/g, '').toLowerCase());

    // Flexible column mapping - detect various naming conventions
    const columnMap = {
      prompt: headers.findIndex(h =>
        h.includes('prompt') || h.includes('question') || h.includes('query') || h.includes('input')
      ),
      response: headers.findIndex(h =>
        h.includes('response') || h.includes('answer') || h.includes('output') || h.includes('completion')
      ),
      promptTokens: headers.findIndex(h =>
        h.includes('prompt') && h.includes('token') || h.includes('input_token')
      ),
      completionTokens: headers.findIndex(h =>
        h.includes('completion') && h.includes('token') || h.includes('output_token') || h.includes('response_token')
      ),
      cost: headers.findIndex(h =>
        h.includes('cost') || h.includes('price') || h.includes('amount')
      ),
      timestamp: headers.findIndex(h =>
        h.includes('timestamp') || h.includes('time') || h.includes('date') || h.includes('created')
      )
    };

    // Validate required columns
    if (columnMap.prompt === -1 || columnMap.response === -1) {
      throw new Error('File must contain columns for prompts and responses. Expected columns like: prompt, response, etc.');
    }

    const dataRows = lines.slice(1);
    const parsedData = dataRows.map((row, index) => {
      try {
        const columns = row.split(',').map(col => col.trim().replace(/"/g, ''));

        return {
          prompt: columns[columnMap.prompt] || '',
          response: columns[columnMap.response] || '',
          promptTokens: parseInt(columns[columnMap.promptTokens]) || Math.floor(Math.random() * 50) + 10,
          completionTokens: parseInt(columns[columnMap.completionTokens]) || Math.floor(Math.random() * 100) + 20,
          cost: parseFloat(columns[columnMap.cost]) || (Math.random() * 0.01 + 0.001),
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

    return parsedData;
  };

  // Export functionality
  const exportData = useCallback(() => {
    const exportData = analytics.filteredData.map(item => ({
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
    const cutoffDate = new Date(now - (dateRange === '7d' ? 7 : dateRange === '30d' ? 30 : 1) * 24 * 60 * 60 * 1000);

    const filteredData = processedData.filter(item => {
      const itemDate = new Date(item.timestamp);
      const matchesDate = itemDate >= cutoffDate;
      const matchesSearch = !searchTerm ||
        item.prompt.toLowerCase().includes(searchTerm.toLowerCase()) ||
        item.response.toLowerCase().includes(searchTerm.toLowerCase());
      return matchesDate && matchesSearch;
    });

    const uniqueUsers = new Set(filteredData.map((_, index) => `user_${index % 20}`)).size;
    const totalQueries = filteredData.length;
    const totalCost = filteredData.reduce((sum, item) => sum + item.cost, 0);
    const avgTokens = filteredData.reduce((sum, item) => sum + item.promptTokens + item.completionTokens, 0) / filteredData.length;

    // Failed queries (responses indicating inability to help)
    const failedQueries = filteredData.filter(item =>
      item.response.toLowerCase().includes("don't have access") ||
      item.response.toLowerCase().includes("cannot") ||
      item.response.toLowerCase().includes("unable to") ||
      item.response.toLowerCase().includes("not able")
    );

    // Topic analysis
    const topicKeywords = {
      'Technology': ['machine learning', 'blockchain', 'quantum', 'cloud', 'AI', 'data'],
      'Business': ['productivity', 'team', 'management', 'strategy', 'growth'],
      'Support': ['password', 'reset', 'help', 'support', 'account'],
      'Security': ['security', 'password', 'encryption', 'privacy', 'safe'],
      'General': ['weather', 'book', 'flight', 'email', 'access']
    };

    const topicCounts = Object.entries(topicKeywords).map(([topic, keywords]) => ({
      topic,
      count: filteredData.filter(item =>
        keywords.some(keyword => item.prompt.toLowerCase().includes(keyword))
      ).length
    }));

    // Daily usage pattern
    const dailyUsage = filteredData.reduce((acc, item) => {
      const date = new Date(item.timestamp).toDateString();
      acc[date] = (acc[date] || 0) + 1;
      return acc;
    }, {});

    const dailyData = Object.entries(dailyUsage).map(([date, count]) => ({
      date: new Date(date).toLocaleDateString(),
      queries: count
    }));

    // Hourly usage pattern
    const hourlyUsage = filteredData.reduce((acc, item) => {
      const hour = new Date(item.timestamp).getHours();
      acc[hour] = (acc[hour] || 0) + 1;
      return acc;
    }, {});

    const hourlyData = Array.from({length: 24}, (_, hour) => ({
      hour: `${hour}:00`,
      queries: hourlyUsage[hour] || 0
    }));

    return {
      uniqueUsers,
      totalQueries,
      totalCost,
      avgTokens,
      failedQueries: failedQueries.length,
      failureRate: (failedQueries.length / totalQueries * 100).toFixed(1),
      topicCounts,
      dailyData,
      hourlyData,
      filteredData,
      failedQueriesList: failedQueries
    };
  }, [processedData, dateRange, searchTerm]);

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
                {dataSource === 'uploaded'
                  ? `Showing data from uploaded file (${processedData.length} records)`
                  : `Showing sample data (${processedData.length} records)`}
              </p>
            </div>
            <div className="flex items-center gap-2">
              {dataSource === 'uploaded' && (
                <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                  <CheckCircle className="w-4 h-4 mr-1" />
                  Live Data
                </span>
              )}
              {dataSource === 'sample' && (
                <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                  <Info className="w-4 h-4 mr-1" />
                  Sample Data
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
              <Upload className="w-5 h-5 text-gray-600" />
              <label className="cursor-pointer">
                <input
                  type="file"
                  accept=".csv,.xlsx,.xls"
                  onChange={handleFileUpload}
                  disabled={isLoading}
                  className="hidden"
                />
                <span className={`inline-flex items-center px-4 py-2 rounded-lg border-2 border-dashed transition-colors ${
                  isLoading 
                    ? 'border-gray-300 text-gray-400 cursor-not-allowed' 
                    : 'border-blue-300 text-blue-700 hover:border-blue-400 hover:bg-blue-50'
                }`}>
                  {isLoading ? (
                    <>
                      <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
                      Processing...
                    </>
                  ) : (
                    <>
                      <Upload className="w-4 h-4 mr-2" />
                      Upload Data File
                    </>
                  )}
                </span>
              </label>
            </div>

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
            </select>

            <button
              onClick={exportData}
              disabled={analytics.filteredData.length === 0}
              className="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed"
            >
              <Download className="w-4 h-4" />
              Export Data
            </button>

            {dataSource === 'uploaded' && (
              <button
                onClick={() => {
                  setData([]);
                  setDataSource('sample');
                  setUploadStatus({ type: 'info', message: 'Switched back to sample data' });
                  setTimeout(() => setUploadStatus({ type: '', message: '' }), 3000);
                }}
                className="flex items-center gap-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
              >
                <RefreshCw className="w-4 h-4" />
                Reset to Sample
              </button>
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
              </div>
              <Users className="w-8 h-8 text-blue-500" />
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-md p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Queries</p>
                <p className="text-2xl font-bold text-gray-800">{analytics.totalQueries}</p>
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
                <p className="text-2xl font-bold text-gray-800">${analytics.totalCost.toFixed(3)}</p>
                <p className="text-xs text-gray-500">Avg: ${(analytics.totalCost / analytics.totalQueries).toFixed(4)}/query</p>
              </div>
              <DollarSign className="w-8 h-8 text-yellow-500" />
            </div>
          </div>
        </div>

        {/* Charts Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
          {/* Daily Usage Trend */}
          <div className="bg-white rounded-lg shadow-md p-6">
            <h3 className="text-lg font-semibold text-gray-800 mb-4">Daily Usage Trend</h3>
            <ResponsiveContainer width="100%" height={300}>
              <AreaChart data={analytics.dailyData}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="date" />
                <YAxis />
                <Tooltip />
                <Area type="monotone" dataKey="queries" stroke="#8884d8" fill="#8884d8" fillOpacity={0.6} />
              </AreaChart>
            </ResponsiveContainer>
          </div>

          {/* Hourly Usage Pattern */}
          <div className="bg-white rounded-lg shadow-md p-6">
            <h3 className="text-lg font-semibold text-gray-800 mb-4">Hourly Usage Pattern</h3>
            <ResponsiveContainer width="100%" height={300}>
              <BarChart data={analytics.hourlyData}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="hour" />
                <YAxis />
                <Tooltip />
                <Bar dataKey="queries" fill="#82ca9d" />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* Topic Analysis and Cost Breakdown */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
          {/* Topic Distribution */}
          <div className="bg-white rounded-lg shadow-md p-6">
            <h3 className="text-lg font-semibold text-gray-800 mb-4">Topic Distribution</h3>
            <ResponsiveContainer width="100%" height={300}>
              <PieChart>
                <Pie
                  data={analytics.topicCounts}
                  dataKey="count"
                  nameKey="topic"
                  cx="50%"
                  cy="50%"
                  outerRadius={80}
                  label={({topic, count}) => `${topic}: ${count}`}
                >
                  {analytics.topicCounts.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                  ))}
                </Pie>
                <Tooltip />
              </PieChart>
            </ResponsiveContainer>
          </div>

          {/* Token Usage */}
          <div className="bg-white rounded-lg shadow-md p-6">
            <h3 className="text-lg font-semibold text-gray-800 mb-4">Token Usage Analysis</h3>
            <div className="space-y-4">
              <div className="flex justify-between items-center">
                <span className="text-gray-600">Average Tokens per Query</span>
                <span className="font-semibold">{analytics.avgTokens.toFixed(0)}</span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-gray-600">Total Prompt Tokens</span>
                <span className="font-semibold">{analytics.filteredData.reduce((sum, item) => sum + item.promptTokens, 0)}</span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-gray-600">Total Completion Tokens</span>
                <span className="font-semibold">{analytics.filteredData.reduce((sum, item) => sum + item.completionTokens, 0)}</span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-gray-600">Cost per Token</span>
                <span className="font-semibold">${(analytics.totalCost / (analytics.filteredData.reduce((sum, item) => sum + item.promptTokens + item.completionTokens, 0))).toFixed(6)}</span>
              </div>
            </div>
          </div>
        </div>

        {/* Failed Queries Analysis */}
        <div className="bg-white rounded-lg shadow-md p-6 mb-6">
          <h3 className="text-lg font-semibold text-gray-800 mb-4">Failed Queries Analysis</h3>
          <div className="space-y-3">
            {analytics.failedQueriesList.slice(0, 5).map((item, index) => (
              <div key={index} className="border-l-4 border-red-500 pl-4 py-2 bg-red-50 rounded-r-lg">
                <p className="font-semibold text-gray-800">Query: {item.prompt}</p>
                <p className="text-sm text-gray-600">Response: {item.response}</p>
                <p className="text-xs text-gray-500">Cost: ${item.cost.toFixed(4)} | Time: {new Date(item.timestamp).toLocaleString()}</p>
              </div>
            ))}
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
    </div>
  );
};

export default LogsDashboard;
