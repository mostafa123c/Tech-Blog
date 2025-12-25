import {
  BrowserRouter as Router,
  Routes,
  Route,
  Navigate,
} from "react-router-dom";
import { Toaster } from "react-hot-toast";
import { AuthProvider, useAuth } from "./context/AuthContext";
import Navbar from "./components/Navbar/Navbar";
import PrivateRoute from "./components/PrivateRoute/PrivateRoute";
import Feed from "./pages/Feed/Feed";
import Login from "./pages/Auth/Login";
import Register from "./pages/Auth/Register";
import PostDetail from "./pages/PostDetail/PostDetail";
import CreatePost from "./pages/PostForm/CreatePost";
import "./components/PrivateRoute/PrivateRoute.css";

const PublicRoute = ({ children }) => {
  const { isAuthenticated, loading } = useAuth();

  if (loading) {
    return (
      <div className="loading-screen">
        <div className="loading-spinner-lg" />
      </div>
    );
  }

  if (isAuthenticated) {
    return <Navigate to="/" replace />;
  }

  return children;
};

function AppContent() {
  return (
    <>
      <Navbar />
      <main>
        <Routes>
          <Route
            path="/login"
            element={
              <PublicRoute>
                <Login />
              </PublicRoute>
            }
          />
          <Route
            path="/register"
            element={
              <PublicRoute>
                <Register />
              </PublicRoute>
            }
          />
          <Route
            path="/"
            element={
              <PrivateRoute>
                <Feed />
              </PrivateRoute>
            }
          />
          <Route
            path="/posts/create"
            element={
              <PrivateRoute>
                <CreatePost />
              </PrivateRoute>
            }
          />
          <Route
            path="/posts/:id"
            element={
              <PrivateRoute>
                <PostDetail />
              </PrivateRoute>
            }
          />
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </main>
    </>
  );
}

function App() {
  return (
    <Router>
      <AuthProvider>
        <AppContent />
        <Toaster
          position="top-right"
          toastOptions={{
            duration: 3000,
            style: {
              background: "#fff",
              color: "#242424",
              borderRadius: "8px",
              padding: "12px 16px",
              fontSize: "14px",
              fontFamily: "'Inter', sans-serif",
              boxShadow: "0 4px 12px rgba(0, 0, 0, 0.15)",
              border: "1px solid rgba(0, 0, 0, 0.08)",
            },
            success: {
              iconTheme: {
                primary: "#1a8917",
                secondary: "#fff",
              },
            },
            error: {
              iconTheme: {
                primary: "#c94a4a",
                secondary: "#fff",
              },
            },
          }}
        />
      </AuthProvider>
    </Router>
  );
}

export default App;
