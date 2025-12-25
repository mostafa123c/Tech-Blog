import { useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import { postsAPI } from "../../services/api";
import {
  HiOutlineArrowLeft,
  HiOutlineXMark,
  HiOutlinePlus,
} from "react-icons/hi2";
import toast from "react-hot-toast";
import "./PostForm.css";

const CreatePost = () => {
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    title: "",
    body: "",
  });
  const [tags, setTags] = useState([]);
  const [tagInput, setTagInput] = useState("");
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState({});

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: null }));
    }
  };

  const handleAddTag = () => {
    const tag = tagInput.trim().toLowerCase();
    if (tag && !tags.includes(tag) && tags.length < 10) {
      setTags([...tags, tag]);
      setTagInput("");
      if (errors.tags) {
        setErrors((prev) => ({ ...prev, tags: null }));
      }
    }
  };

  const handleTagKeyDown = (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      handleAddTag();
    }
  };

  const handleRemoveTag = (tagToRemove) => {
    setTags(tags.filter((tag) => tag !== tagToRemove));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (tags.length === 0) {
      setErrors({ tags: ["At least one tag is required"] });
      toast.error("Please add at least one tag");
      return;
    }

    setLoading(true);
    setErrors({});

    try {
      const response = await postsAPI.create({
        title: formData.title,
        body: formData.body,
        tags: tags,
      });

      if (response.data.success) {
        toast.success("Post created!");
        navigate(`/posts/${response.data.data.id}`);
      }
    } catch (error) {
      const errorData = error.response?.data;
      if (errorData?.errors) {
        setErrors(errorData.errors);
      }
      toast.error(errorData?.message || "Failed to create post");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="post-form-page">
      <div className="container">
        <Link to="/" className="back-link">
          <HiOutlineArrowLeft />
          <span>Back to Feed</span>
        </Link>

        <div className="post-form-container">
          <div className="form-header">
            <h1>Create Post</h1>
            <p>Share your thoughts • Expires in 24 hours</p>
          </div>

          <form onSubmit={handleSubmit} className="post-form">
            <div className="form-group">
              <label htmlFor="title">Title</label>
              <input
                type="text"
                id="title"
                name="title"
                value={formData.title}
                onChange={handleChange}
                placeholder="Enter post title..."
                required
                maxLength={255}
              />
              {errors.title && (
                <span className="error-text">{errors.title[0]}</span>
              )}
            </div>

            <div className="form-group">
              <label htmlFor="body">Content</label>
              <textarea
                id="body"
                name="body"
                value={formData.body}
                onChange={handleChange}
                placeholder="Write your post content..."
                required
                rows={8}
              />
              {errors.body && (
                <span className="error-text">{errors.body[0]}</span>
              )}
            </div>

            <div className="form-group">
              <label>
                Tags <span className="required">*</span>
              </label>
              <div className="tags-input-container">
                {tags.length > 0 && (
                  <div className="tags-list">
                    {tags.map((tag) => (
                      <span key={tag} className="tag-chip">
                        {tag}
                        <button
                          type="button"
                          onClick={() => handleRemoveTag(tag)}
                        >
                          <HiOutlineXMark />
                        </button>
                      </span>
                    ))}
                  </div>
                )}
                <div className="tag-input-row">
                  <input
                    type="text"
                    value={tagInput}
                    onChange={(e) => setTagInput(e.target.value)}
                    onKeyDown={handleTagKeyDown}
                    placeholder="Type a tag and press Enter..."
                    disabled={tags.length >= 10}
                  />
                  <button
                    type="button"
                    className="add-tag-btn"
                    onClick={handleAddTag}
                    disabled={!tagInput.trim() || tags.length >= 10}
                  >
                    <HiOutlinePlus />
                  </button>
                </div>
              </div>
              {errors.tags && (
                <span className="error-text">{errors.tags[0]}</span>
              )}
            </div>

            <div className="form-actions">
              <Link to="/" className="cancel-btn">
                Cancel
              </Link>
              <button type="submit" className="submit-btn" disabled={loading}>
                {loading ? (
                  <span className="loading-spinner" />
                ) : (
                  "Publish Post"
                )}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
};

export default CreatePost;
