import { useMemo, useState } from "react";
import { Link, useParams } from "react-router-dom";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { blogPosts } from "@/data/blogPosts";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import {
  Facebook,
  Instagram,
  Linkedin,
  MessageCircle,
  RefreshCcw,
  Twitter,
} from "lucide-react";

const buildCaptcha = () => {
  const chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
  let result = "";
  for (let i = 0; i < 6; i += 1) {
    result += chars[Math.floor(Math.random() * chars.length)];
  }
  return result;
};

const BlogDetail = () => {
  const { slug } = useParams();
  const blog = useMemo(() => blogPosts.find((post) => post.slug === slug), [slug]);

  const [captcha, setCaptcha] = useState(buildCaptcha());
  const [captchaInput, setCaptchaInput] = useState("");

  const refreshCaptcha = () => {
    setCaptcha(buildCaptcha());
    setCaptchaInput("");
  };

  if (!blog) {
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

  const moreBlogs = blogPosts.filter((post) => post.slug !== blog.slug).slice(0, 2);

  return (
    <div className="min-h-screen bg-background">
      <Navbar />
      <main className="pt-16">
        <section className="py-12">
          <div className="container mx-auto px-4">
            <div className="grid gap-6 lg:grid-cols-[1.6fr_0.8fr]">
              <div className="rounded-2xl border border-border bg-card shadow-card overflow-hidden">
                <div className="aspect-[16/9] overflow-hidden">
                  <img src={blog.image} alt={blog.title} className="h-full w-full object-cover" />
                </div>
              </div>

              <div className="rounded-2xl border border-border bg-card p-6 shadow-card">
                <h3 className="text-xl font-heading font-bold text-foreground">Get Free Consultation</h3>
                <form className="mt-4 space-y-3">
                  <Input placeholder="Full name" required />
                  <Input type="email" placeholder="Your Email" />
                  <Input type="tel" placeholder="Phone" required />
                  <Textarea placeholder="Message" rows={4} />
                  <div className="grid gap-3 md:grid-cols-[1fr_1fr]">
                    <div className="flex items-center gap-3">
                      <div className="flex-1 rounded-full border border-border bg-muted px-4 py-2 text-center text-sm font-semibold text-primary">
                        {captcha}
                      </div>
                      <button
                        type="button"
                        className="inline-flex h-9 w-9 items-center justify-center rounded-full border border-border text-muted-foreground hover:text-foreground"
                        onClick={refreshCaptcha}
                        aria-label="Refresh captcha"
                      >
                        <RefreshCcw className="h-4 w-4" />
                      </button>
                    </div>
                    <Input
                      placeholder="Enter Captcha"
                      value={captchaInput}
                      onChange={(event) => setCaptchaInput(event.target.value)}
                    />
                  </div>
                  <Button variant="hero" className="w-full">Submit</Button>
                </form>
              </div>
            </div>

            <div className="mt-8 max-w-4xl">
              <div className="flex flex-wrap items-center justify-between gap-4">
                <h1 className="text-2xl md:text-3xl font-heading font-bold text-foreground">
                  {blog.title}
                </h1>
                <Button variant="secondary" className="rounded-full">
                  <MessageCircle className="mr-2 h-4 w-4" />
                  WHATSAPP US
                </Button>
              </div>
              <p className="mt-3 text-sm text-muted-foreground">{blog.date} · {blog.readTime}</p>
              <p className="mt-4 text-muted-foreground text-base leading-relaxed">{blog.excerpt}</p>

              <div className="mt-6 space-y-6">
                {blog.content.map((section) => (
                  <div key={section.heading}>
                    <h2 className="text-lg font-semibold text-foreground">{section.heading}</h2>
                    <div className="mt-2 space-y-3 text-muted-foreground">
                      {section.body.map((paragraph) => (
                        <p key={paragraph}>{paragraph}</p>
                      ))}
                    </div>
                  </div>
                ))}
              </div>

              <div className="mt-10 flex items-center gap-4">
                <span className="text-sm font-semibold text-foreground">Share</span>
                <div className="flex items-center gap-3 text-primary">
                  <button type="button" className="inline-flex h-9 w-9 items-center justify-center rounded-full border border-border text-muted-foreground hover:text-primary">
                    <Facebook className="h-4 w-4" />
                  </button>
                  <button type="button" className="inline-flex h-9 w-9 items-center justify-center rounded-full border border-border text-muted-foreground hover:text-primary">
                    <Twitter className="h-4 w-4" />
                  </button>
                  <button type="button" className="inline-flex h-9 w-9 items-center justify-center rounded-full border border-border text-muted-foreground hover:text-primary">
                    <Instagram className="h-4 w-4" />
                  </button>
                  <button type="button" className="inline-flex h-9 w-9 items-center justify-center rounded-full border border-border text-muted-foreground hover:text-primary">
                    <Linkedin className="h-4 w-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section className="py-12 bg-white">
          <div className="container mx-auto px-4">
            <h2 className="text-2xl font-heading font-bold text-foreground">More Blogs</h2>
            <div className="mt-6 grid gap-6 md:grid-cols-2">
              {moreBlogs.map((item) => (
                <article key={item.slug} className="rounded-2xl border border-border bg-white shadow-card overflow-hidden">
                  <div className="aspect-[16/9] overflow-hidden">
                    <img src={item.image} alt={item.title} className="h-full w-full object-cover" />
                  </div>
                  <div className="p-5">
                    <h3 className="text-lg font-semibold text-foreground leading-snug">{item.title}</h3>
                    <p className="mt-2 text-xs text-muted-foreground">{item.date}</p>
                    <Button asChild variant="hero" className="mt-4">
                      <Link to={`/blogs/${item.slug}`}>Read Full Blog</Link>
                    </Button>
                  </div>
                </article>
              ))}
            </div>
            <div className="mt-6">
              <Button asChild variant="hero">
                <Link to="/blogs">View More Blogs</Link>
              </Button>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default BlogDetail;
