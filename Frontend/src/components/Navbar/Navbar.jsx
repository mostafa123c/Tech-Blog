import { Link, useNavigate, useLocation } from "react-router-dom";
import { useAuth } from "../../context/AuthContext";
import { STORAGE_URL } from "../../config/api";
import {
  HiOutlineHome,
  HiOutlinePlusCircle,
  HiOutlineArrowRightOnRectangle,
  HiOutlineUser,
} from "react-icons/hi2";
import "./Navbar.css";

const Navbar = () => {
  const { user, isAuthenticated, logout } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  const handleLogout = async () => {
    await logout();
    navigate("/login");
  };

  const getImageUrl = (imagePath) => {
    if (!imagePath) return null;
    return `${STORAGE_URL}/${imagePath}`;
  };

  return (
    <nav className="navbar">
      <div className="navbar-container">
        <Link to="/" className="navbar-brand">
          <span className="brand-text">TechBlog</span>
        </Link>

        <div className="navbar-links">
          {isAuthenticated ? (
            <>
              <Link
                to="/"
                className={`nav-link ${
                  location.pathname === "/" ? "active" : ""
                }`}
              >
                <HiOutlineHome />
                <span>Feed</span>
              </Link>

              <Link to="/posts/create" className="nav-link primary">
                <HiOutlinePlusCircle />
                <span>Create Post</span>
              </Link>

              <div className="nav-divider" />

              <div className="user-menu">
                <div className="user-avatar">
                  {user?.image ? (
                    <img src={getImageUrl(user.image)} alt={user.name} />
                  ) : (
                    <HiOutlineUser />
                  )}
                </div>
                <span className="user-name">{user?.name}</span>
              </div>

              <button onClick={handleLogout} className="nav-link logout-btn">
                <HiOutlineArrowRightOnRectangle />
                <span>Logout</span>
              </button>
            </>
          ) : (
            <>
              <Link
                to="/login"
                className={`nav-link ${
                  location.pathname === "/login" ? "active" : ""
                }`}
              >
                Login
              </Link>
              <Link to="/register" className="nav-link primary">
                Sign Up
              </Link>
            </>
          )}
        </div>
      </div>
    </nav>
  );
};

export default Navbar;
