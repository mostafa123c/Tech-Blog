import { useState, useEffect, useCallback } from "react";
import { postsAPI } from "../../services/api";
import PostCard from "../../components/PostCard/PostCard";
import { HiOutlineArrowPath } from "react-icons/hi2";
import toast from "react-hot-toast";
import "./Feed.css";

const Feed = () => {
  const [posts, setPosts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);
  const [hasMore, setHasMore] = useState(true);
  const [page, setPage] = useState(1);

  const fetchPosts = useCallback(
    async (pageNum = 1, isRefresh = false, isLoadMore = false) => {
      try {
        if (isRefresh) setRefreshing(true);
        else if (isLoadMore) setLoadingMore(true);
        else setLoading(true);

        const response = await postsAPI.getAll(pageNum);
        const data = response.data;

        if (data.success) {
          const newPosts = data.data.items || [];

          if (isLoadMore) {
            setPosts((prev) => [...prev, ...newPosts]);
          } else {
            setPosts(newPosts);
          }

          setHasMore(data.data.next_page_url !== null);
        }
        setError(null);
      } catch (err) {
        console.error("Error fetching posts:", err);
        setError("Failed to load posts");
        if (!isLoadMore) toast.error("Failed to load posts");
      } finally {
        setLoading(false);
        setLoadingMore(false);
        setRefreshing(false);
      }
    },
    []
  );

  useEffect(() => {
    fetchPosts(1);
  }, [fetchPosts]);

  useEffect(() => {
    const interval = setInterval(() => {
      fetchPosts(1, true);
      setPage(1);
    }, 60000);
    return () => clearInterval(interval);
  }, [fetchPosts]);

  const handleRefresh = () => {
    setPage(1);
    fetchPosts(1, true);
  };

  const handleLoadMore = () => {
    const nextPage = page + 1;
    setPage(nextPage);
    fetchPosts(nextPage, false, true);
  };

  if (loading) {
    return (
      <div className="feed-page">
        <div className="container">
          <div className="feed-header">
            <div className="skeleton" style={{ width: 120, height: 28 }} />
          </div>
          <div className="posts-list">
            {[1, 2, 3, 4].map((i) => (
              <div
                key={i}
                className="skeleton"
                style={{ height: 100, marginBottom: 16 }}
              />
            ))}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="feed-page">
      <div className="container">
        <div className="feed-header">
          <h1 className="feed-title">Latest</h1>
          <button
            className={`refresh-btn ${refreshing ? "refreshing" : ""}`}
            onClick={handleRefresh}
            disabled={refreshing}
          >
            <HiOutlineArrowPath />
          </button>
        </div>

        {error && (
          <div className="error-banner">
            {error}
            <button onClick={() => fetchPosts(1)}>Retry</button>
          </div>
        )}

        {posts.length === 0 && !error ? (
          <div className="empty-state">
            <h2>No posts yet</h2>
            <p>Be the first to share something</p>
          </div>
        ) : (
          <>
            <div className="posts-list">
              {posts.map((post) => (
                <PostCard key={post.id} post={post} />
              ))}
            </div>

            {hasMore && (
              <div className="load-more-container">
                <button
                  className="load-more-btn"
                  onClick={handleLoadMore}
                  disabled={loadingMore}
                >
                  {loadingMore ? "Loading..." : "Show more"}
                </button>
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
};

export default Feed;
