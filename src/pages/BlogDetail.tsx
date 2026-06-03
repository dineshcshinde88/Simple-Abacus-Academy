import { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { fetchBlogDetail, BlogPost } from "@/services/blogApi";
import { blogPosts } from "@/data/blogPosts";

const formatDate = (value?: string | null) =>
  value ? new Date(value).toLocaleDateString("en-IN", { day: "2-digit", month: "long", year: "numeric" }) : "";

const BlogDetail = () => {
  const { slug } = useParams();
  const [blog, setBlog] = useState<BlogPost | null>(null);
  const [staticBlog, setStaticBlog] = useState<(typeof blogPosts)[number] | null>(null);
  const [loading, setLoading] = useState(true);
  const [notFound, setNotFound] = useState(false);

  useEffect(() => {
    if (!slug) return;
    setLoading(true);
    setStaticBlog(null);
    fetchBlogDetail(slug)
      .then((data) => {
        setBlog(data.blog);
        setNotFound(false);
      })
      .catch(() => {
        const fallback = blogPosts.find((post) => post.slug === slug) || null;
        setBlog(null);
        setStaticBlog(fallback);
        setNotFound(!fallback);
      })
      .finally(() => setLoading(false));
  }, [slug]);

  if (loading) {
    return (
      <div className="min-h-screen bg-background">
        <Navbar />
        <main className="pt-16">
          <section className="py-16">
            <div className="container mx-auto px-4 text-center text-muted-foreground">Loading blog...</div>
          </section>
        </main>
        <Footer />
      </div>
    );
  }

  if (staticBlog) {
    return (
      <div className="min-h-screen bg-background">
        <Navbar />
        <main className="pt-16">
          <article className="py-12">
            <div className="container mx-auto px-4">
              <div className="mx-auto max-w-4xl">
                <div className="overflow-hidden rounded-lg border border-border bg-muted shadow-card">
                  <img src={staticBlog.image} alt={staticBlog.title} className="h-auto w-full object-cover" />
                </div>
                <div className="mt-8">
                  <div className="text-sm font-semibold uppercase text-primary">Blog</div>
                  <h1 className="mt-3 text-3xl md:text-4xl font-heading font-bold text-foreground">{staticBlog.title}</h1>
                  <div className="mt-4 flex flex-wrap gap-4 text-sm text-muted-foreground">
                    <span>Published on: {staticBlog.date}</span>
                    <span>Author: Admin</span>
                  </div>
                  <p className="mt-5 text-lg leading-relaxed text-muted-foreground">{staticBlog.excerpt}</p>
                </div>
                <div className="mt-8 space-y-6 text-foreground">
                  {staticBlog.content.map((section) => (
                    <section key={section.heading}>
                      <h2 className="text-xl font-heading font-bold">{section.heading}</h2>
                      <div className="mt-3 space-y-3 text-muted-foreground">
                        {section.body.map((paragraph) => <p key={paragraph}>{paragraph}</p>)}
                      </div>
                    </section>
                  ))}
                </div>
              </div>
            </div>
          </article>
        </main>
        <Footer />
      </div>
    );
  }

  if (notFound || !blog) {
    return (
      <div className="min-h-screen bg-background">
        <Navbar />
        <main className="pt-16">
          <section className="py-16">
            <div className="container mx-auto px-4 text-center">
              <h1 className="text-3xl font-heading font-bold text-foreground">Blog Not Found</h1>
              <p className="mt-2 text-muted-foreground">The blog you are looking for does not exist.</p>
              <Button asChild variant="hero" className="mt-6">
                <Link to="/blogs">Back to Blogs</Link>
              </Button>
            </div>
          </section>
        </main>
        <Footer />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background">
      <Navbar />
      <main className="pt-16">
        <article className="py-12">
          <div className="container mx-auto px-4">
            <div className="mx-auto max-w-4xl">
              {blog.featured_image_url ? (
                <div className="overflow-hidden rounded-lg border border-border bg-muted shadow-card">
                  <img src={blog.featured_image_url} alt={blog.title} className="h-auto w-full object-cover" />
                </div>
              ) : null}

              <div className="mt-8">
                <div className="text-sm font-semibold uppercase text-primary">{blog.category_name || "Blog"}</div>
                <h1 className="mt-3 text-3xl md:text-4xl font-heading font-bold text-foreground">{blog.title}</h1>
                <div className="mt-4 flex flex-wrap gap-4 text-sm text-muted-foreground">
                  <span>Published on: {formatDate(blog.publish_date)}</span>
                  <span>Author: {blog.author || "Admin"}</span>
                </div>
                {blog.excerpt ? <p className="mt-5 text-lg leading-relaxed text-muted-foreground">{blog.excerpt}</p> : null}
              </div>

              <div
                className="prose prose-slate mt-8 max-w-none text-foreground prose-headings:font-heading prose-a:text-primary"
                dangerouslySetInnerHTML={{ __html: blog.description || "" }}
              />
            </div>
          </div>
        </article>
      </main>
      <Footer />
    </div>
  );
};

export default BlogDetail;
