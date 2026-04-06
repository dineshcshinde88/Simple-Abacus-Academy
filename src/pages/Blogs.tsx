import { Link } from "react-router-dom";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { blogPosts } from "@/data/blogPosts";

const Blogs = () => (
  <div className="min-h-screen bg-background">
    <Navbar />
    <main className="pt-16">
      <section className="py-16">
        <div className="container mx-auto px-4">
          <div className="mb-10">
            <h1 className="text-3xl md:text-4xl font-heading font-bold text-foreground">Latest Blogs</h1>
            <p className="mt-2 text-muted-foreground">Insights, tips, and resources for students, parents, and teachers.</p>
          </div>
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {blogPosts.map((blog) => (
              <article key={blog.slug} className="rounded-2xl border border-border bg-white shadow-card overflow-hidden">
                <div className="aspect-[4/3] overflow-hidden">
                  <img src={blog.image} alt={blog.title} className="h-full w-full object-cover" />
                </div>
                <div className="p-4">
                  <h3 className="text-lg font-semibold text-foreground leading-snug line-clamp-2">{blog.title}</h3>
                  <p className="mt-2 text-xs text-muted-foreground">{blog.date} · {blog.readTime}</p>
                  <p className="mt-3 text-sm text-muted-foreground line-clamp-3">{blog.excerpt}</p>
                  <Button asChild className="mt-4 w-full bg-[#4B1E83] hover:bg-[#3c176a]">
                    <Link to={`/blogs/${blog.slug}`}>Read Full Blog</Link>
                  </Button>
                </div>
              </article>
            ))}
          </div>
        </div>
      </section>
    </main>
    <Footer />
  </div>
);

export default Blogs;
