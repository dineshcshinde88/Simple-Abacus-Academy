import WorksheetSubscriptionLayout from "@/components/worksheets/WorksheetSubscriptionLayout";
import { placeholderImages } from "@/data/placeholderImages";

const WorksheetsSubscription = () => (
  <WorksheetSubscriptionLayout
    title="Worksheet Subscription"
    subtitle="Downloadable worksheets for self-paced learning"
    description="Get access to high-quality printable worksheets designed to improve speed, accuracy, and confidence in maths."
    heroImage={placeholderImages.worksheetPractice}
    dashboardImage={placeholderImages.aboutHero}
  />
);

export default WorksheetsSubscription;
