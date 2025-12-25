import { useState, useEffect, useRef } from "react";
import { useParams, useNavigate, Link } from "react-router-dom";
import { postsAPI, commentsAPI } from "../../services/api";
import { useAuth } from "../../context/AuthContext";
import { STORAGE_URL } from "../../config/api";
import { formatDistanceToNow, differenceInHours } from "date-fns";
import {
  HiOutlineUser,
  HiOutlineClock,
  HiOutlinePencil,
  HiOutlineTrash,
  HiOutlineArrowLeft,
  HiOutlineXMark,
  HiOutlinePlus,
} from "react-icons/hi2";
import toast from "react-hot-toast";
import "./PostDetail.css";

const PostDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();

  const [post, setPost] = useState(null);
  const [comments, setComments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [loadingComments, setLoadingComments] = useState(false);
  const [newComment, setNewComment] = useState("");
  const [submittingComment, setSubmittingComment] = useState(false);
  const [editingComment, setEditingComment] = useState(null);
  const [editCommentText, setEditCommentText] = useState("");
  const [deleting, setDeleting] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [commentToDelete, setCommentToDelete] = useState(null);
  const [commentsPage, setCommentsPage] = useState(1);
  const [hasMoreComments, setHasMoreComments] = useState(true);

  // Edit post modal state
  const [showEditModal, setShowEditModal] = useState(false);
  const [editFormData, setEditFormData] = useState({ title: "", body: "" });
  const [editTags, setEditTags] = useState([]);
  const [editTagInput, setEditTagInput] = useState("");
  const [editLoading, setEditLoading] = useState(false);
  const [editErrors, setEditErrors] = useState({});

  useEffect(() => {
    fetchPost();
    fetchComments(1);
  }, [id]);

  const fetchPost = async () => {
    try {
      const response = await postsAPI.getOne(id);
      if (response.data.success) {
        setPost(response.data.data);
      }
    } catch (err) {
      if (err.response?.status === 404) {
        toast.error("Post not found");
        navigate("/");
      }
    } finally {
      setLoading(false);
    }
  };

  const fetchComments = async (pageNum = 1, isLoadMore = false) => {
    try {
      if (isLoadMore) setLoadingComments(true);

      const response = await commentsAPI.getByPost(id, pageNum);
      if (response.data.success) {
        const newComments = response.data.data.items || [];

        if (isLoadMore) {
          setComments((prev) => [...prev, ...newComments]);
        } else {
          setComments(newComments);
        }

        setHasMoreComments(response.data.data.next_page_url !== null);
      }
    } catch (err) {
      console.error("Error fetching comments:", err);
    } finally {
      setLoadingComments(false);
    }
  };

  // Infinite scroll for comments using window scroll
  const loadingRef = useRef(false);
  const initialLoadDone = useRef(false);

  useEffect(() => {
    if (comments.length > 0 || !hasMoreComments) {
      setTimeout(() => {
        initialLoadDone.current = true;
      }, 500);
    }
  }, [comments, hasMoreComments]);

  useEffect(() => {
    const handleScroll = () => {
      if (!initialLoadDone.current || loadingRef.current || !hasMoreComments)
        return;

      const scrollTop = window.scrollY;
      const windowHeight = window.innerHeight;
      const docHeight = document.documentElement.scrollHeight;

      // Load more when near bottom (200px from bottom)
      if (scrollTop + windowHeight >= docHeight - 200) {
        loadingRef.current = true;
        setCommentsPage((prev) => {
          const nextPage = prev + 1;
          loadMoreComments(nextPage);
          return nextPage;
        });
      }
    };

    window.addEventListener("scroll", handleScroll);
    return () => window.removeEventListener("scroll", handleScroll);
  }, [hasMoreComments]);

  const loadMoreComments = async (pageNum) => {
    setLoadingComments(true);
    try {
      const response = await commentsAPI.getByPost(id, pageNum);
      if (response.data.success) {
        const newComments = response.data.data.items || [];
        setComments((prev) => [...prev, ...newComments]);
        setHasMoreComments(response.data.data.next_page_url !== null);
      }
    } catch (error) {
      console.error("Error loading more comments:", error);
    } finally {
      setLoadingComments(false);
      loadingRef.current = false;
    }
  };

  const handleDeletePost = async () => {
    setDeleting(true);
    try {
      await postsAPI.delete(id);
      toast.success("Post deleted");
      navigate("/");
    } catch (err) {
      toast.error("Failed to delete post");
    } finally {
      setDeleting(false);
      setShowDeleteModal(false);
    }
  };

  const handleAddComment = async (e) => {
    e.preventDefault();
    if (!newComment.trim()) return;

    setSubmittingComment(true);
    try {
      const response = await commentsAPI.create(id, newComment);
      if (response.data.success) {
        setComments([response.data.data, ...comments]);
        setNewComment("");
        toast.success("Comment added!");
      }
    } catch (err) {
      toast.error("Failed to add comment");
    } finally {
      setSubmittingComment(false);
    }
  };

  const handleEditComment = async (commentId) => {
    if (!editCommentText.trim()) return;

    try {
      await commentsAPI.update(commentId, editCommentText);
      setComments(
        comments.map((c) =>
          c.id === commentId ? { ...c, body: editCommentText } : c
        )
      );
      setEditingComment(null);
      toast.success("Comment updated!");
    } catch (err) {
      toast.error("Failed to update comment");
    }
  };

  const handleDeleteComment = async () => {
    if (!commentToDelete) return;

    try {
      await commentsAPI.delete(commentToDelete);
      setComments(comments.filter((c) => c.id !== commentToDelete));
      toast.success("Comment deleted!");
    } catch (err) {
      toast.error("Failed to delete comment");
    } finally {
      setCommentToDelete(null);
    }
  };

  // Edit Post Modal Functions
  const openEditModal = () => {
    setEditFormData({ title: post.title, body: post.body });
    setEditTags(post.tags?.map((t) => t.name) || []);
    setEditTagInput("");
    setEditErrors({});
    setShowEditModal(true);
  };

  const handleEditChange = (e) => {
    const { name, value } = e.target;
    setEditFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleAddEditTag = () => {
    const tag = editTagInput.trim().toLowerCase();
    if (tag && !editTags.includes(tag) && editTags.length < 10) {
      setEditTags([...editTags, tag]);
      setEditTagInput("");
    }
  };

  const handleEditTagKeyDown = (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      handleAddEditTag();
    }
  };

  const handleRemoveEditTag = (tagToRemove) => {
    setEditTags(editTags.filter((tag) => tag !== tagToRemove));
  };

  const handleEditSubmit = async (e) => {
    e.preventDefault();

    if (editTags.length === 0) {
      setEditErrors({ tags: ["At least one tag is required"] });
      toast.error("Please add at least one tag");
      return;
    }

    setEditLoading(true);
    try {
      const response = await postsAPI.update(id, {
        title: editFormData.title,
        body: editFormData.body,
        tags: editTags,
      });

      if (response.data.success) {
        setPost(response.data.data);
        setShowEditModal(false);
        toast.success("Post updated!");
      }
    } catch (err) {
      const errorData = err.response?.data;
      if (errorData?.errors) {
        setEditErrors(errorData.errors);
      }
      toast.error(errorData?.message || "Failed to update post");
    } finally {
      setEditLoading(false);
    }
  };

  const getImageUrl = (imagePath) => {
    if (!imagePath) return null;
    return `${STORAGE_URL}/${imagePath}`;
  };

  const getExpiryInfo = () => {
    if (!post?.expires_at) return null;
    const expiresAt = new Date(post.expires_at);
    const hoursLeft = differenceInHours(expiresAt, new Date());
    return hoursLeft > 0 ? hoursLeft : 0;
  };

  if (loading) {
    return (
      <div className="post-detail-page">
        <div className="container">
          <div className="skeleton" style={{ height: 400 }} />
        </div>
      </div>
    );
  }

  if (!post) {
    return (
      <div className="post-detail-page">
        <div className="container">
          <div className="not-found">
            <h2>Post not found</h2>
            <p>This post may have expired or been deleted.</p>
            <Link to="/" className="back-btn">
              ← Back to Feed
            </Link>
          </div>
        </div>
      </div>
    );
  }

  const isAuthor = user?.id === post.user?.id;
  const hoursLeft = getExpiryInfo();

  return (
    <div className="post-detail-page">
      <div className="container">
        <Link to="/" className="back-link">
          <HiOutlineArrowLeft />
          <span>Back to Feed</span>
        </Link>

        <article className="post-detail">
          <div className="post-header">
            <div className="author-section">
              <div className="author-avatar">
                {post.user?.image ? (
                  <img
                    src={getImageUrl(post.user.image)}
                    alt={post.user.name}
                  />
                ) : (
                  <HiOutlineUser />
                )}
              </div>
              <div className="author-info">
                <span className="author-name">
                  {post.user?.name || "Anonymous"}
                </span>
                <span className="post-meta">
                  {formatDistanceToNow(new Date(post.created_at), {
                    addSuffix: true,
                  })}
                  {hoursLeft !== null && (
                    <span
                      className={`expiry-inline ${
                        hoursLeft <= 2 ? "urgent" : ""
                      }`}
                    >
                      · {hoursLeft}h left
                    </span>
                  )}
                </span>
              </div>
            </div>

            {isAuthor && (
              <div className="author-actions">
                <button onClick={openEditModal} className="action-btn">
                  <HiOutlinePencil />
                </button>
                <button
                  onClick={() => setShowDeleteModal(true)}
                  className="action-btn delete"
                >
                  <HiOutlineTrash />
                </button>
              </div>
            )}
          </div>

          <h1 className="post-title">{post.title}</h1>

          {post.tags && post.tags.length > 0 && (
            <div className="post-tags">
              {post.tags.map((tag) => (
                <span key={tag.id} className="tag">
                  {tag.name}
                </span>
              ))}
            </div>
          )}

          <div className="post-body">
            {post.body.split("\n").map((paragraph, index) => (
              <p key={index}>{paragraph}</p>
            ))}
          </div>
        </article>

        <section className="comments-section">
          <h2 className="comments-title">
            Comments {post.comments_count > 0 && `(${post.comments_count})`}
          </h2>

          <form onSubmit={handleAddComment} className="comment-form">
            <textarea
              value={newComment}
              onChange={(e) => setNewComment(e.target.value)}
              placeholder="What are your thoughts?"
              rows={3}
            />
            <button
              type="submit"
              className="submit-comment-btn"
              disabled={!newComment.trim() || submittingComment}
            >
              {submittingComment ? "Commenting..." : "Comment"}
            </button>
          </form>

          <div className="comments-list">
            {comments.map((comment) => (
              <div key={comment.id} className="comment-card">
                <div className="comment-header">
                  <div className="comment-author">
                    <div className="comment-avatar">
                      {comment.user?.image ? (
                        <img
                          src={getImageUrl(comment.user.image)}
                          alt={comment.user.name}
                        />
                      ) : (
                        <HiOutlineUser />
                      )}
                    </div>
                    <div>
                      <span className="comment-author-name">
                        {comment.user?.name}
                      </span>
                      <span className="comment-date">
                        {formatDistanceToNow(new Date(comment.created_at), {
                          addSuffix: true,
                        })}
                      </span>
                    </div>
                  </div>

                  {user?.id === comment.user?.id && (
                    <div className="comment-actions">
                      <button
                        onClick={() => {
                          setEditingComment(comment.id);
                          setEditCommentText(comment.body);
                        }}
                      >
                        <HiOutlinePencil />
                      </button>
                      <button onClick={() => setCommentToDelete(comment.id)}>
                        <HiOutlineTrash />
                      </button>
                    </div>
                  )}
                </div>

                {editingComment === comment.id ? (
                  <div className="edit-comment">
                    <textarea
                      value={editCommentText}
                      onChange={(e) => setEditCommentText(e.target.value)}
                      rows={3}
                    />
                    <div className="edit-actions">
                      <button
                        onClick={() => setEditingComment(null)}
                        className="cancel-btn"
                      >
                        Cancel
                      </button>
                      <button
                        onClick={() => handleEditComment(comment.id)}
                        className="save-btn"
                      >
                        Save
                      </button>
                    </div>
                  </div>
                ) : (
                  <p className="comment-body">{comment.body}</p>
                )}
              </div>
            ))}

            {comments.length === 0 && (
              <div className="no-comments">
                <p>No Comments yet. Be the first to share your thoughts!</p>
              </div>
            )}

            {/* Comments loading indicator */}
            {loadingComments && (
              <div className="comments-loader">
                <div className="loading-spinner-sm" />
              </div>
            )}
          </div>
        </section>
      </div>

      {/* Edit Post Modal */}
      {showEditModal && (
        <div className="modal-overlay" onClick={() => setShowEditModal(false)}>
          <div
            className="modal-content edit-modal"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="modal-header">
              <h3>Edit Post</h3>
              <button
                className="modal-close"
                onClick={() => setShowEditModal(false)}
              >
                <HiOutlineXMark />
              </button>
            </div>

            <form onSubmit={handleEditSubmit} className="edit-form">
              <div className="form-group">
                <label>Title</label>
                <input
                  type="text"
                  name="title"
                  value={editFormData.title}
                  onChange={handleEditChange}
                  required
                />
                {editErrors.title && (
                  <span className="error-text">{editErrors.title[0]}</span>
                )}
              </div>

              <div className="form-group">
                <label>Content</label>
                <textarea
                  name="body"
                  value={editFormData.body}
                  onChange={handleEditChange}
                  required
                  rows={6}
                />
                {editErrors.body && (
                  <span className="error-text">{editErrors.body[0]}</span>
                )}
              </div>

              <div className="form-group">
                <label>Tags</label>
                <div className="tags-input-container">
                  {editTags.length > 0 && (
                    <div className="tags-list">
                      {editTags.map((tag) => (
                        <span key={tag} className="tag-chip">
                          {tag}
                          <button
                            type="button"
                            onClick={() => handleRemoveEditTag(tag)}
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
                      value={editTagInput}
                      onChange={(e) => setEditTagInput(e.target.value)}
                      onKeyDown={handleEditTagKeyDown}
                      placeholder="Add tag..."
                      disabled={editTags.length >= 10}
                    />
                    <button
                      type="button"
                      className="add-tag-btn"
                      onClick={handleAddEditTag}
                      disabled={!editTagInput.trim() || editTags.length >= 10}
                    >
                      <HiOutlinePlus />
                    </button>
                  </div>
                </div>
                {editErrors.tags && (
                  <span className="error-text">{editErrors.tags[0]}</span>
                )}
              </div>

              <div className="modal-actions">
                <button
                  type="button"
                  className="modal-btn cancel"
                  onClick={() => setShowEditModal(false)}
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="modal-btn confirm"
                  disabled={editLoading}
                >
                  {editLoading ? "Saving..." : "Save Changes"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Delete Post Modal */}
      {showDeleteModal && (
        <div
          className="modal-overlay"
          onClick={() => setShowDeleteModal(false)}
        >
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <button
              className="modal-close"
              onClick={() => setShowDeleteModal(false)}
            >
              <HiOutlineXMark />
            </button>
            <h3>Delete Post?</h3>
            <p>
              This action cannot be undone. Your post will be permanently
              removed.
            </p>
            <div className="modal-actions">
              <button
                className="modal-btn cancel"
                onClick={() => setShowDeleteModal(false)}
              >
                Cancel
              </button>
              <button
                className="modal-btn delete"
                onClick={handleDeletePost}
                disabled={deleting}
              >
                {deleting ? "Deleting..." : "Delete"}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Delete Comment Modal */}
      {commentToDelete && (
        <div className="modal-overlay" onClick={() => setCommentToDelete(null)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <button
              className="modal-close"
              onClick={() => setCommentToDelete(null)}
            >
              <HiOutlineXMark />
            </button>
            <h3>Delete Response?</h3>
            <p>This action cannot be undone.</p>
            <div className="modal-actions">
              <button
                className="modal-btn cancel"
                onClick={() => setCommentToDelete(null)}
              >
                Cancel
              </button>
              <button
                className="modal-btn delete"
                onClick={handleDeleteComment}
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default PostDetail;
