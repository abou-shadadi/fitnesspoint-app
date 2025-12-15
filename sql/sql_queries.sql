ALTER TABLE public.expenses ALTER COLUMN "name" TYPE json USING "name"::text::json;

TRUNCATE TABLE public.expenses CONTINUE IDENTITY CASCADE;
ALTER TABLE public.expenses ALTER COLUMN "name" TYPE json USING "name"::text::json;

ALTER TABLE public.expenses ALTER COLUMN description TYPE json USING description::text::json;


ALTER TABLE public.expense_items ALTER COLUMN "name" TYPE json USING "name"::text::json;

ALTER TABLE public.expense_items ALTER COLUMN description TYPE json USING description::text::json;

TRUNCATE TABLE public.incomes CONTINUE IDENTITY CASCADE;
ALTER TABLE public.incomes ALTER COLUMN "name" TYPE json USING "name"::text::json;


ALTER TABLE public.incomes ALTER COLUMN description TYPE json USING description::text::json;


ALTER TABLE public.income_items ALTER COLUMN "name" TYPE json USING "name"::text::json;


ALTER TABLE public.income_items ALTER COLUMN description TYPE json USING description::text::json;


# New
ALTER TABLE public.student_deposit_transaction_expense_items ALTER COLUMN budget_expense_id DROP NOT NULL;


ALTER TABLE public.student_academic_enrollments ADD annual_percentage varchar NULL;

ALTER TABLE public.student_academic_enrollments ADD annual_rank varchar NULL;
ALTER TABLE public.student_academic_enrollments ADD annual_conduct varchar NULL;


ALTER TABLE public.student_academic_enrollment_semesters RENAME COLUMN "position" TO "rank";


ALTER TABLE public.student_academic_enrollment_semesters ADD conduct varchar NULL;
