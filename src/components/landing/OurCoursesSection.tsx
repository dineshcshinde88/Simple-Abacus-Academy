import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { placeholderImages } from "@/data/placeholderImages";

const courses = [
  {
    title: "Junior Abacus",
    meta: "6 Levels | Age 6-8 Years",
    body: "Play-based mental arithmetic that builds focus and number sense in young learners.",
    tags: ["Better Focus", "Strong Memory", "Brain Growth", "Math Confidence"],
    image: placeholderImages.ourCourses,
    to: "/online-abacus-classes",
  },
  {
    title: "Senior Abacus",
    meta: "10 Levels | Age 8-12 Years",
    body: "Structured training for speed, accuracy, and advanced mental calculation.",
    tags: ["Fast Calculations", "High Accuracy", "Logical Thinking", "Exam Ready"],
    image: placeholderImages.aboutAcademy,
    to: "/online-abacus-classes",
  },
  {
    title: "Vedic Maths",
    meta: "4 Levels | Age 11-14 Years",
    body: "Smart techniques to simplify calculations and reduce errors.",
    tags: ["Quick Methods", "Less Errors", "Sharp Mind", "Better Results"],
    image: placeholderImages.moduleSection,
    to: "/vedic-maths-classes",
  },
];

const OurCoursesSection = () => (
  <section className="py-16">
    <div className="container mx-auto px-4">
      <div className="text-center">
        <h2 className="text-3xl md:text-4xl font-heading font-extrabold text-primary">
          Our Courses
        </h2>
      </div>

      <div className="mt-10 grid gap-6 lg:grid-cols-3">
        {courses.map((course) => (
          <div
            key={course.title}
            className="rounded-2xl border border-secondary/20 bg-white shadow-card overflow-hidden flex flex-col"
          >
            <div className="h-56 md:h-64 w-full overflow-hidden">
              <img
                src={course.image}
                alt={course.title}
                className="h-full w-full object-cover"
                loading="lazy"
              />
            </div>
            <div className="p-6 flex flex-col flex-1">
              <h3 className="text-lg font-semibold text-primary">{course.title}</h3>
              <div className="mt-1 text-xs text-muted-foreground">{course.meta}</div>
              <p className="mt-3 text-sm text-muted-foreground">{course.body}</p>

              <div className="mt-4 grid grid-cols-2 gap-2 text-xs">
                {course.tags.map((tag, index) => (
                  <span
                    key={`${course.title}-${tag}`}
                    className={`rounded-full px-3 py-1 text-center font-medium ${
                      index % 2 === 0 ? "bg-secondary/10 text-secondary" : "bg-primary/5 text-primary"
                    }`}
                  >
                    {tag}
                  </span>
                ))}
              </div>

              <div className="mt-6">
                <Button variant="hero" asChild>
                  <Link to={course.to}>View Syllabus</Link>
                </Button>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  </section>
);

export default OurCoursesSection;
