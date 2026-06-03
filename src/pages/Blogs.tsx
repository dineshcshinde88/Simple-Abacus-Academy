import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { fetchBlogs } from "@/services/blogApi";
import { blogPosts } from "@/data/blogPosts";

const formatDate = (value?: string | null) =>
  value ? new Date(value).toLocaleDateString("en-IN", { day: "2-digit", month: "long", year: "numeric" }) : "";

const Blogs = () => {
  const [search, setSearch] = useState("");
  const [category, setCategory] = useState("all");
  const [page, setPage] = useState(1);
  const [data, setData] = useState<Awaited<ReturnType<typeof fetchBlogs>> | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    setLoading(true);
    fetchBlogs({ search, category: category === "all" ? "" : category, page, limit: 9 })
      .then(setData)
      .catch(() => setData({ blogs: [], categories: [], total: 0, page: 1, totalPages: 0 }))
      .finally(() => setLoading(false));
  }, [category, page, search]);

  return (
    <div className="min-h-screen bg-background">
      <Navbar />
      <main className="pt-16">
        <section className="py-16">
          <div className="container mx-auto px-4">
            <div className="mb-8 flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
              <div>
                <h1 className="text-3xl md:text-4xl font-heading font-bold text-foreground">Latest Blogs</h1>
                <p className="mt-2 text-muted-foreground">Insights, tips, and resources for students, parents, and teachers.</p>
              </div>
              <div className="grid gap-3 sm:grid-cols-[260px_220px]">
                <Input value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} placeholder="Search blogs" />
                <Select value={category} onValueChange={(value) => { setCategory(value); setPage(1); }}>
                  <SelectTrigger><SelectValue placeholder="Filter by category" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Categories</SelectItem>
                    {(data?.categories || []).map((item) => (
                      <SelectItem key={item.slug} value={item.slug}>{item.category_name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>

            {loading ? (
              <div className="rounded-lg border border-border bg-white p-8 text-center text-muted-foreground">Loading blogs...</div>
            ) : (
              <>
                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                  {(data?.blogs || []).map((blog) => (
                    <article key={blog.slug} className="overflow-hidden rounded-lg border border-border bg-white shadow-card">
                      <div className="aspect-[4/3] overflow-hidden bg-muted">
                        {blog.featured_image_url ? <img src={blog.featured_image_url} alt={blog.title} className="h-full w-full object-cover" /> : null}
                      </div>
                      <div className="p-5">
                        <div className="text-xs font-semibold uppercase text-primary">{blog.category_name || "Blog"}</div>
                        <h3 className="mt-2 text-lg font-semibold text-foreground leading-snug line-clamp-2">{blog.title}</h3>
                        <p className="mt-2 text-xs text-muted-foreground">{formatDate(blog.publish_date)} | {blog.author || "Admin"}</p>
                        <p className="mt-3 text-sm text-muted-foreground line-clamp-3">{blog.excerpt}</p>
                        <Button asChild className="mt-4 w-full bg-[#4B1E83] hover:bg-[#3c176a]">
                          <Link to={`/blog/${blog.slug}`}>Read More</Link>
                        </Button>
                      </div>
                    </article>
                  ))}
                  {blogPosts
                    .filter((blog) => {
                      const query = search.trim().toLowerCase();
                      const isManaged = (data?.blogs || []).some((item) => item.slug === blog.slug);
                      return !isManaged && category === "all" && (!query || blog.title.toLowerCase().includes(query) || blog.excerpt.toLowerCase().includes(query));
                    })
                    .map((blog) => (
                      <article key={blog.slug} className="overflow-hidden rounded-lg border border-border bg-white shadow-card">
                        <div className="aspect-[4/3] overflow-hidden bg-muted">
                          <img src={blog.image} alt={blog.title} className="h-full w-full object-cover" />
                        </div>
                        <div className="p-5">
                          <div className="text-xs font-semibold uppercase text-primary">Blog</div>
                          <h3 className="mt-2 text-lg font-semibold text-foreground leading-snug line-clamp-2">{blog.title}</h3>
                          <p className="mt-2 text-xs text-muted-foreground">{blog.date} | Admin</p>
                          <p className="mt-3 text-sm text-muted-foreground line-clamp-3">{blog.excerpt}</p>
                          <Button asChild className="mt-4 w-full bg-[#4B1E83] hover:bg-[#3c176a]">
                            <Link to={`/blog/${blog.slug}`}>Read More</Link>
                          </Button>
                        </div>
                      </article>
                    ))}
                </div>
                {(data?.blogs || []).length === 0 && blogPosts.length === 0 ? (
                  <div className="rounded-lg border border-border bg-white p-8 text-center text-muted-foreground">No blogs found.</div>
                ) : null}
                {(data?.totalPages || 0) > 1 ? (
                  <div className="mt-8 flex justify-center gap-2">
                    {Array.from({ length: data?.totalPages || 0 }, (_, index) => index + 1).map((item) => (
                      <Button key={item} variant={item === page ? "default" : "outline"} onClick={() => setPage(item)}>{item}</Button>
                    ))}
                  </div>
                ) : null}
              </>
            )}
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default Blogs;
