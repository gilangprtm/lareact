import * as React from "react"
import {
  ChevronLeftIcon,
  ChevronRightIcon,
  MoreHorizontalIcon,
} from "lucide-react"

import { cn } from "@/lib/utils"
import { Button, buttonVariants } from "@/components/ui/button"
import { Link } from "@inertiajs/react"

function Pagination({ className, ...props }: React.ComponentProps<"nav">) {
  return (
    <nav
      data-slot="pagination"
      className={cn("mx-auto flex w-full justify-center", className)}
      {...props}
    />
  )
}

function PaginationContent({
  className,
  ...props
}: React.ComponentProps<"ul">) {
  return (
    <ul
      data-slot="pagination-content"
      className={cn("flex flex-row items-center gap-1", className)}
      {...props}
    />
  )
}

function PaginationItem({ ...props }: React.ComponentProps<"li">) {
  return <li data-slot="pagination-item" {...props} />
}

function PaginationLink({
  className,
  isActive,
  size = "icon",
  href = "#",
  ...props
}: {
  className?: string
  isActive?: boolean
  size?: "default" | "sm" | "lg" | "icon"
  href?: string
  [key: string]: any
}) {
  return (
    <Link
      href={href}
      aria-current={isActive ? "page" : undefined}
      className={cn(
        buttonVariants({
          variant: isActive ? "default" : "ghost",
          size,
        }),
        className
      )}
      {...props}
    />
  )
}

function PaginationPrevious({
  className,
  href = "#",
  ...props
}: {
  className?: string
  href?: string
  [key: string]: any
}) {
  return (
    <PaginationLink
      href={href}
      aria-label="Go to previous page"
      size="default"
      className={cn("gap-1 px-2.5 sm:pl-2.5", className)}
      {...props}
    >
      Previous
    </PaginationLink>
  )
}

function PaginationNext({
  className,
  href = "#",
  ...props
}: {
  className?: string
  href?: string
  [key: string]: any
}) {
  return (
    <PaginationLink
      href={href}
      aria-label="Go to next page"
      size="default"
      className={cn("gap-1 px-2.5 sm:pr-2.5", className)}
      {...props}
    >
      Next
    </PaginationLink>
  )
}

function PaginationEllipsis({
  className,
  ...props
}: React.ComponentProps<"span">) {
  return (
    <span
      data-slot="pagination-ellipsis"
      className={cn("flex h-9 w-9 items-center justify-center", className)}
      {...props}
    >
      ...
    </span>
  )
}

export {
  Pagination,
  PaginationContent,
  PaginationLink,
  PaginationItem,
  PaginationPrevious,
  PaginationNext,
  PaginationEllipsis,
}
