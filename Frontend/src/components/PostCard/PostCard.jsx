import { Link } from "react-router-dom";
import { formatDistanceToNow, differenceInHours } from "date-fns";
import { STORAGE_URL } from "../../config/api";
import { HiOutlineClock, HiOutlineUser } from "react-icons/hi2";
import "./PostCard.css";

const PostCard = ({ post }) => {
  const getImageUrl = (imagePath) => {
    if (!imagePath) return null;
    return `${STORAGE_URL}/${imagePath}`;
  };

  const getExpiryInfo = () => {
    if (!post.expires_at) return null;
    const expiresAt = new Date(post.expires_at);
    const hoursLeft = differenceInHours(expiresAt, new Date());
    if (hoursLeft <= 0) return { text: "Expiring", urgent: true };
    if (hoursLeft <= 3) return { text: `${hoursLeft}h left`, urgent: true };
    return { text: `${hoursLeft}h left`, urgent: false };
  };

  const expiryInfo = getExpiryInfo();
  const tags = post.tags || [];
  const visibleTags = tags.slice(0, 2);
  const remainingTags = tags.length - 2;

  return (
    <article className="post-card">
      <Link to={`/posts/${post.id}`} className="post-card-link">
        <div className="post-card-header">
          <div className="header-left">
            <div className="author-avatar">
              {post.user?.image ? (
                <img src={getImageUrl(post.user.image)} alt={post.user.name} />
              ) : (
                <HiOutlineUser />
              )}
            </div>
            <span className="author-name">
              {post.user?.name || "Anonymous"}
            </span>
            <span className="dot">·</span>
            <span className="post-date">
              {formatDistanceToNow(new Date(post.created_at), {
                addSuffix: false,
              })}
            </span>
          </div>
          {expiryInfo && (
            <span
              className={`expiry-badge ${expiryInfo.urgent ? "urgent" : ""}`}
            >
              <HiOutlineClock />
              {expiryInfo.text}
            </span>
          )}
        </div>

        <h2 className="post-title">{post.title}</h2>
        <p className="post-excerpt">{post.body}</p>

        {tags.length > 0 && (
          <div className="post-tags">
            {visibleTags.map((tag) => (
              <span key={tag.id} className="tag">
                {tag.name}
              </span>
            ))}
            {remainingTags > 0 && (
              <span className="tag tag-more">+{remainingTags}</span>
            )}
          </div>
        )}
      </Link>
    </article>
  );
};

export default PostCard;
