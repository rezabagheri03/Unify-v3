import React from 'react';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Skeleton from '@mui/material/Skeleton';

/** P18 SkeletonCards — loading placeholders matching CourseCard / FileCard. */
export function CourseCardSkeleton({ count = 3 }: { count?: number }) {
  return (
    <>
      {Array.from({ length: count }).map((_, i) => (
        <Card key={i} sx={{ mb: 1 }}>
          <CardContent>
            <Skeleton variant="text" width="50%" />
            <Skeleton variant="text" width="35%" />
            <Skeleton variant="text" width="70%" />
          </CardContent>
        </Card>
      ))}
    </>
  );
}

export function FileCardSkeleton({ count = 3 }: { count?: number }) {
  return (
    <>
      {Array.from({ length: count }).map((_, i) => (
        <Card key={i} sx={{ mb: 1 }}>
          <CardContent sx={{ display: 'flex', gap: 2 }}>
            <Skeleton variant="rectangular" width={44} height={56} />
            <Skeleton variant="text" width="60%" />
          </CardContent>
        </Card>
      ))}
    </>
  );
}
